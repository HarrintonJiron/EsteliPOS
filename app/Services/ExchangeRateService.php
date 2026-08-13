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
        $from = strtoupper($from);
        $to = strtoupper($to);
        $cacheKey = "exchange_rate_{$from}_{$to}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($from, $to) {
            return ExchangeRate::getCurrentRate($from, $to);
        });
    }

    /**
     * Multiplicador efectivo (directo o inverso) cacheado.
     */
    public function resolveMultiplier(string $from, string $to): ?float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = "exchange_multiplier_{$from}_{$to}";
        $cached = Cache::get($cacheKey);

        if (is_numeric($cached)) {
            return (float) $cached;
        }

        $multiplier = ExchangeRate::resolveMultiplier($from, $to);

        if ($multiplier !== null) {
            Cache::put($cacheKey, $multiplier, now()->addHours(1));
        }

        return $multiplier;
    }

    /**
     * Convertir un monto de una moneda a otra
     */
    public function convert(float $amount, string $from, string $to): float
    {
        $multiplier = $this->resolveMultiplier($from, $to);

        if ($multiplier === null) {
            return 0;
        }

        return round($amount * $multiplier, 4);
    }

    /**
     * Obtener todas las tasas activas
     */
    public function getActiveRates(): array
    {
        $rates = ExchangeRate::query()
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', now()->toDateString())
            ->orderByDesc('effective_date')
            ->get()
            ->groupBy(fn (ExchangeRate $rate) => $rate->from_currency.'_'.$rate->to_currency)
            ->map(fn ($group) => $group->first());

        return $rates->values()->all();
    }

    /**
     * Crear una nueva tasa de cambio
     */
    public function createRate(array $data): ExchangeRate
    {
        $rate = ExchangeRate::query()->create([
            'from_currency' => strtoupper($data['from_currency']),
            'to_currency' => strtoupper($data['to_currency']),
            'rate' => $data['rate'],
            'effective_date' => $data['effective_date'] ?? now()->toDateString(),
            'is_active' => $data['is_active'] ?? true,
        ]);

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

        $this->clearCache($from, $to);
    }

    private function clearCache(string $from, string $to): void
    {
        Cache::forget("exchange_rate_{$from}_{$to}");
        Cache::forget("exchange_rate_{$to}_{$from}");
        Cache::forget("exchange_multiplier_{$from}_{$to}");
        Cache::forget("exchange_multiplier_{$to}_{$from}");
    }
}
