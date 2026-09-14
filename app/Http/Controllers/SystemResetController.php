<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetSystemRequest;
use App\Services\SystemResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class SystemResetController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless($request->user()?->hasPermission('configuracion.reset_system'), 403);

        return view('settings.system-reset');
    }

    public function store(ResetSystemRequest $request, SystemResetService $resetter): RedirectResponse
    {
        try {
            $result = $resetter->reset($request->user(), $request->string('mode')->toString());
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->with('error', $exception->getMessage());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = $result['mode'] === 'clean'
            ? "Sistema entregado en limpio correctamente. Respaldo creado: {$result['backup_name']}"
            : "Demostración recargada correctamente. Respaldo creado: {$result['backup_name']}";

        return redirect()->route('login')->with('success', $message);
    }
}
