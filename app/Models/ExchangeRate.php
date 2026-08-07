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

    protected $casts = [
        'rate' => 'decimal:6',
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Obtener la tasa de conversión más reciente para un par de monedas
     */
    public static function getCurrentRate(string $from, string $to): ?self
    {
        return self::where('from_currency', strtoupper($from))
            ->where('to_currency', strtoupper($to))
            ->where('is_active', true)
            ->where('effective_date', '<=', now()->toDateString())
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    /**
     * Convertir un monto de una moneda a otra
     */
    public static function convert(float $amount, string $from, string $to): float
    {
        $rate = self::getCurrentRate($from, $to);

        if (! $rate) {
            return 0;
        }

        return $amount * $rate->rate;
    }
}
