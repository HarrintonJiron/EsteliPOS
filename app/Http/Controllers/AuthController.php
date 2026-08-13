<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ModuleAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request, ModuleAccessService $modules)
    {
        $request->merge(['login' => trim((string) ($request->input('login') ?: $request->input('email')))]);
        $validated = $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required',
        ]);

        $field = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [$field => $validated['login'], 'password' => $validated['password'], 'is_active' => true];
        $throttleKey = Str::lower($validated['login']).'|'.$request->ip();
        $maxAttempts = max(1, min(10, (int) Setting::get('max_login_attempts', 5)));

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'login' => "Demasiados intentos. Intenta nuevamente en {$seconds} segundos.",
            ])->onlyInput('login', 'email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $request->user()->forceFill(['last_login_at' => now()])->save();

            return redirect()->intended($modules->defaultHomeUrl($request->user()));
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'login' => 'Las credenciales no coinciden o el usuario está inactivo.',
            'email' => 'Las credenciales no coinciden o el usuario está inactivo.',
        ])->onlyInput('login', 'email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
