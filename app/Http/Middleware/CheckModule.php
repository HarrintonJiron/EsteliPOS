<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Module;
use App\Services\ModuleAccessService;

class CheckModule
{
    public function __construct(private readonly ModuleAccessService $access) {}
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $moduleSlug): Response
    {
        if ($request->user() && ! $request->user()->isActive()) {
            auth()->logout();

            return redirect()->route('login')->with('error', 'Tu cuenta ha sido desactivada.');
        }

        if (! Module::where('slug', $moduleSlug)->exists() || ! Module::isActive($moduleSlug)) {
            abort(404, 'El módulo no está activo.');
        }

        if (! $this->access->canAccessSlug($moduleSlug, $request->user())) {
            abort(403, 'Tu rol no tiene acceso a este módulo.');
        }

        return $next($request);
    }
}
