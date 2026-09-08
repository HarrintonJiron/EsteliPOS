<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'effective_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'effective_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Obtener la tasa de conversión más reciente para un par de monedas
     */
    public static function getCurrentRate(string $from, string $to): ?self
    {
        return self::query()
            ->where('from_currency', strtoupper($from))
            ->where('to_currency', strtoupper($to))
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', now()->toDateString())
            ->orderByDesc('effective_date')
            ->first();
    }

    /**
     * Multiplicador para convertir amount * multiplier = destino.
     * Usa tasa directa o inversa (1/rate) si solo existe el par inverso.
     */
    public static function resolveMultiplier(string $from, string $to): ?float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return 1.0;
        }

        $direct = self::getCurrentRate($from, $to);
        if ($direct !== null && (float) $direct->rate > 0) {
            return (float) $direct->rate;
        }

        $inverse = self::getCurrentRate($to, $from);
        if ($inverse !== null && (float) $inverse->rate > 0) {
            return 1 / (float) $inverse->rate;
        }

        return null;
    }

    /**
     * Convertir un monto de una moneda a otra
     */
    public static function convert(float $amount, string $from, string $to): float
    {
        $multiplier = self::resolveMultiplier($from, $to);

        if ($multiplier === null) {
            return 0;
        }

        return round($amount * $multiplier, 4);
    }
}
