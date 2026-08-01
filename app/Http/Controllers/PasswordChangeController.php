<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeOwnPasswordRequest;
use App\Models\AuditLog;
use App\Support\PasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password', ['passwordPolicy' => PasswordPolicy::summary()]);
    }

    public function update(ChangeOwnPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update([
            'password' => $request->validated('password'),
            'force_password_change' => false,
            'password_changed_at' => now(),
        ]);
        AuditLog::log('user.password_changed', 'El usuario cambió su contraseña', $user);

        return redirect()->route('dashboard.general')->with('success', 'Contraseña actualizada correctamente.');
    }
}
