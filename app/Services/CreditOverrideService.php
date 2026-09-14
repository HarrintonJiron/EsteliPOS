<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CreditOverrideService
{
    public const TTL_MINUTES = 5;

    public function issue(User $cashier, User $administrator, Client $client, float $maximumAmount): string
    {
        $token = Str::random(64);
        $amount = round($maximumAmount, 2);

        Cache::put($this->key($token), [
            'cashier_id' => $cashier->id,
            'administrator_id' => $administrator->id,
            'client_id' => $client->id,
            'maximum_amount' => $amount,
        ], now()->addMinutes(self::TTL_MINUTES));

        AuditLog::query()->create([
            'user_id' => $administrator->id,
            'action' => 'credit.override.authorized',
            'model_type' => Client::class,
            'model_id' => $client->id,
            'description' => "Exceso de crédito autorizado para {$client->name}",
            'new_values' => [
                'cashier_id' => $cashier->id,
                'maximum_amount' => $amount,
                'expires_in_minutes' => self::TTL_MINUTES,
            ],
        ]);

        return $token;
    }

    public function consume(string $token, User $cashier, Client $client, float $actualAmount): ?int
    {
        if ($token === '') {
            return null;
        }

        $authorization = Cache::pull($this->key($token));
        if (! is_array($authorization)
            || (int) ($authorization['cashier_id'] ?? 0) !== (int) $cashier->id
            || (int) ($authorization['client_id'] ?? 0) !== (int) $client->id
            || round($actualAmount, 2) > (float) ($authorization['maximum_amount'] ?? 0) + 0.01) {
            return null;
        }

        return (int) $authorization['administrator_id'];
    }

    private function key(string $token): string
    {
        return 'credit-override:'.hash('sha256', $token);
    }
}
