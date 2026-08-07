<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;

class ExchangeRateService
{
    /**
     * Obtener la tasa de conversión actual con caché
     */
    public function getCurrentRate(string $from, string $to): ?ExchangeRate
    {
        $cacheKey = "exchange_rate_{$from}_{$to}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($from, $to) {
            return ExchangeRate::getCurrentRate($from, $to);
        });
    }

    /**
     * Convertir un monto de una moneda a otra
     */
    public function convert(float $amount, string $from, string $to): float
    {
        $rate = $this->getCurrentRate($from, $to);

        if (! $rate) {
            return 0;
        }

        return $amount * $rate->rate;
    }

    /**
     * Obtener todas las tasas activas
     */
    public function getActiveRates(): array
    {
        $rates = ExchangeRate::where('is_active', true)
            ->where('effective_date', '<=', now()->toDateString())
            ->orderBy('effective_date', 'desc')
            ->get()
            ->groupBy(['from_currency', 'to_currency'])
            ->map(function ($group) {
                return $group->first();
            });

        return $rates->values()->toArray();
    }

    /**
     * Crear una nueva tasa de cambio
     */
    public function createRate(array $data): ExchangeRate
    {
        $rate = ExchangeRate::create([
            'from_currency' => strtoupper($data['from_currency']),
            'to_currency' => strtoupper($data['to_currency']),
            'rate' => $data['rate'],
            'effective_date' => $data['effective_date'] ?? now()->toDateString(),
            'is_active' => $data['is_active'] ?? true,
        ]);

        // Limpiar caché
        $this->clearCache($rate->from_currency, $rate->to_currency);

        return $rate;
    }

    /**
     * Actualizar una tasa de cambio
     */
    public function updateRate(ExchangeRate $rate, array $data): ExchangeRate
    {
        $rate->update([
            'from_currency' => strtoupper($data['from_currency'] ?? $rate->from_currency),
            'to_currency' => strtoupper($data['to_currency'] ?? $rate->to_currency),
            'rate' => $data['rate'] ?? $rate->rate,
            'effective_date' => $data['effective_date'] ?? $rate->effective_date,
            'is_active' => $data['is_active'] ?? $rate->is_active,
        ]);

        // Limpiar caché
        $this->clearCache($rate->from_currency, $rate->to_currency);

        return $rate->fresh();
    }

    /**
     * Eliminar una tasa de cambio
     */
    public function deleteRate(ExchangeRate $rate): void
    {
        $from = $rate->from_currency;
        $to = $rate->to_currency;

        $rate->delete();

        // Limpiar caché
        $this->clearCache($from, $to);
    }

    /**
     * Limpiar caché de tasas de cambio
     */
    private function clearCache(string $from, string $to): void
    {
        Cache::forget("exchange_rate_{$from}_{$to}");
        Cache::forget("exchange_rate_{$to}_{$from}");
    }
}
