<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->force_password_change && ! $request->routeIs('password.change', 'password.update', 'logout', 'auth.switch-user')) {
            return redirect()->route('password.change')->with('warning', 'Debes cambiar tu contraseña temporal antes de continuar.');
        }

        return $next($request);
    }
}
