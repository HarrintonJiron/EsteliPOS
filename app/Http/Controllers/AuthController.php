<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ModuleAccessService;

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

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $request->user()->forceFill(['last_login_at' => now()])->save();

            $destination = $modules->canAccessSlug('caja', $request->user())
                ? route('arqueo.index')
                : route('dashboard.general');

            return redirect()->intended($destination);
        }

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
