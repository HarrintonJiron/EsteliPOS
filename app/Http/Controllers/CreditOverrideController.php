<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthorizeCreditOverrideRequest;
use App\Models\Client;
use App\Models\User;
use App\Services\CreditOverrideService;
use App\Services\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreditOverrideController extends Controller
{
    public function __invoke(
        AuthorizeCreditOverrideRequest $request,
        CreditOverrideService $overrides,
        CreditService $credit,
    ): JsonResponse {
        $login = mb_strtolower(trim($request->string('admin_login')->toString()));
        $administrator = User::query()
            ->where(function ($query) use ($login) {
                $query->whereRaw('LOWER(email) = ?', [$login])
                    ->orWhereRaw('LOWER(username) = ?', [$login]);
            })
            ->first();

        if (! $administrator
            || ! $administrator->isActive()
            || ! $administrator->isAdmin()
            || ! Hash::check($request->string('password')->toString(), $administrator->password)) {
            throw ValidationException::withMessages([
                'admin_login' => 'Las credenciales del administrador no son válidas.',
            ]);
        }

        $client = Client::query()->findOrFail($request->integer('client_id'));
        $amount = round($request->float('amount'), 2);
        $available = $credit->availableCredit($client);

        if (! $client->credit_enabled || (float) $client->credit_limit <= 0 || $amount <= $available) {
            throw ValidationException::withMessages([
                'amount' => 'Este ticket no requiere una autorización de exceso de crédito.',
            ]);
        }

        return response()->json([
            'token' => $overrides->issue($request->user(), $administrator, $client, $amount),
            'administrator' => $administrator->name,
            'expires_in_minutes' => CreditOverrideService::TTL_MINUTES,
        ]);
    }
}
