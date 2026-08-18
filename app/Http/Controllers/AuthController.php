<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\ModuleAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        $quickUsers = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'email', 'profile_photo', 'pin_hash'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'login' => $user->username ?: $user->email,
                'profile_photo' => $user->profile_photo,
                'has_pin' => filled($user->pin_hash),
            ]);

        return view('auth.login', compact('quickUsers'));
    }

    public function login(Request $request, ModuleAccessService $modules)
    {
        $request->merge([
            'login' => trim((string) ($request->input('login') ?: $request->input('email'))),
            'auth_method' => $request->input('auth_method', 'password'),
        ]);
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'login' => 'required_without:user_id|nullable|string|max:255',
            'auth_method' => 'required|in:password,pin',
            'password' => 'required_if:auth_method,password|nullable|string',
            'pin' => 'required_if:auth_method,pin|nullable|digits_between:4,8',
        ]);

        $user = isset($validated['user_id'])
            ? User::query()->whereKey($validated['user_id'])->where('is_active', true)->first()
            : null;
        $identity = $user?->username ?: $user?->email ?: (string) ($validated['login'] ?? 'unknown');
        $throttleKey = Str::lower($identity).'|'.$request->ip();
        $maxAttempts = max(1, min(10, (int) Setting::get('max_login_attempts', 5)));

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'login' => "Demasiados intentos. Intenta nuevamente en {$seconds} segundos.",
            ])->onlyInput('login', 'email');
        }

        $authenticated = false;
        if ($validated['auth_method'] === 'pin') {
            if (! $user && filled($validated['login'] ?? null)) {
                $field = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
                $user = User::query()->where($field, $validated['login'])->where('is_active', true)->first();
            }

            if ($user?->pin_hash && Hash::check($validated['pin'], $user->pin_hash)) {
                Auth::login($user, $request->boolean('remember'));
                $authenticated = true;
            }
        } else {
            if ($user) {
                $authenticated = Hash::check($validated['password'], $user->password);
                if ($authenticated) {
                    Auth::login($user, $request->boolean('remember'));
                }
            } else {
                $field = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
                $authenticated = Auth::attempt([
                    $field => $validated['login'],
                    'password' => $validated['password'],
                    'is_active' => true,
                ], $request->boolean('remember'));
            }
        }

        if ($authenticated) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $request->user()->forceFill(['last_login_at' => now()])->save();

            return redirect()->intended($modules->defaultHomeUrl($request->user()));
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'login' => 'Las credenciales no coinciden, el PIN no está configurado o el usuario está inactivo.',
        ])->onlyInput('login', 'email', 'user_id', 'auth_method');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function switchUser(Request $request, ModuleAccessService $modules)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'pin' => ['required', 'digits_between:4,8'],
        ]);

        $target = User::query()
            ->whereKey($validated['user_id'])
            ->where('is_active', true)
            ->first();
        $throttleKey = 'switch-user|'.$validated['user_id'].'|'.$request->ip();
        $maxAttempts = max(1, min(10, (int) Setting::get('max_login_attempts', 5)));

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'switch_user' => "Demasiados intentos. Intenta nuevamente en {$seconds} segundos.",
            ]);
        }

        if (! $target?->pin_hash || ! Hash::check($validated['pin'], $target->pin_hash)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'switch_user' => 'El PIN no coincide, no está configurado o el usuario está inactivo.',
            ])->withInput('user_id');
        }

        $previousUser = $request->user();
        AuditLog::log(
            'user.quick_switch',
            "Cambio rápido de {$previousUser->name} a {$target->name}",
            $target,
            ['user_id' => $previousUser->id],
            ['user_id' => $target->id],
        );

        RateLimiter::clear($throttleKey);
        Auth::login($target);
        $request->session()->regenerate();
        $target->forceFill(['last_login_at' => now()])->save();

        return redirect($modules->defaultHomeUrl($target))
            ->with('success', "Usuario cambiado a {$target->name}.");
    }
}
