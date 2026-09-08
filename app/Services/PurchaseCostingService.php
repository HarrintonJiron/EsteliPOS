<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use RuntimeException;

class PurchaseCostingService
{
    public function __construct(
        private ExchangeRateService $exchangeRates,
        private UnitConversionService $units,
        private CompanySettingsService $companySettings,
    ) {}

    public function companyCurrency(): string
    {
        return strtoupper((string) ($this->companySettings->get()['currency'] ?? 'NIO'));
    }

    public function companySymbol(): string
    {
        return (string) ($this->companySettings->get()['currency_symbol'] ?? 'C$');
    }

    /**
     * Currency shown as the "other" equivalence (USD↔NIO relative to company settings).
     */
    public function equivalenceCurrency(): string
    {
        return $this->companyCurrency() === 'USD' ? 'NIO' : 'USD';
    }

    /**
     * FX snapshot for POS reference labels (invoice stays in company currency).
     *
     * @return array{
     *     company_currency: string,
     *     company_symbol: string,
     *     reference_currency: string,
     *     reference_symbol: string,
     *     reference_rate: ?float
     * }
     */
    public function posReferenceFx(): array
    {
        $company = $this->companyCurrency();
        $reference = $this->equivalenceCurrency();
        $rate = $company === $reference
            ? 1.0
            : $this->exchangeRates->resolveMultiplier($company, $reference);

        return [
            'company_currency' => $company,
            'company_symbol' => $this->companySymbol(),
            'reference_currency' => $reference,
            'reference_symbol' => $this->currencySymbol($reference),
            'reference_rate' => ($rate !== null && $rate > 0) ? round((float) $rate, 6) : null,
        ];
    }

    /**
     * @return list<string>
     */
    public function supportedCurrencies(): array
    {
        return ['NIO', 'USD', 'EUR'];
    }

    public function currencySymbol(string $currency): string
    {
        return match (strtoupper($currency)) {
            'USD' => 'US$',
            'EUR' => '€',
            'NIO' => 'C$',
            default => $this->companySymbol(),
        };
    }

    /**
     * Rate that converts 1 unit of $from into company currency.
     */
    public function resolveExchangeRate(string $fromCurrency, ?float $override = null): float
    {
        $from = strtoupper($fromCurrency);
        $to = $this->companyCurrency();

        if ($from === $to) {
            return 1.0;
        }

        if ($override !== null) {
            if ($override <= 0) {
                throw new RuntimeException('El tipo de cambio debe ser mayor que cero.');
            }

            return round($override, 6);
        }

        $rate = $this->exchangeRates->resolveMultiplier($from, $to);

        if ($rate === null || $rate <= 0) {
            throw new RuntimeException("No hay tipo de cambio activo de {$from} a {$to}. Configúralo en Ajustes → Tipos de cambio.");
        }

        return round($rate, 6);
    }

    public function documentCurrency(Purchase $purchase): string
    {
        return strtoupper((string) ($purchase->currency ?: $this->companyCurrency()));
    }

    public function documentTotal(Purchase $purchase): float
    {
        if ($purchase->foreign_total !== null) {
            return round((float) $purchase->foreign_total, 2);
        }

        return round((float) $purchase->total, 2);
    }

    public function companyTotal(Purchase $purchase): float
    {
        return round((float) $purchase->total, 2);
    }

    /**
     * Convert a company-currency amount into the equivalence currency.
     */
    public function toEquivalenceAmount(float $companyAmount, ?Purchase $purchase = null): ?float
    {
        $company = $this->companyCurrency();
        $target = $this->equivalenceCurrency();

        if ($company === $target) {
            return round($companyAmount, 2);
        }

        if ($purchase !== null) {
            $documentCurrency = $this->documentCurrency($purchase);
            $rate = (float) ($purchase->exchange_rate ?: 0);

            if ($documentCurrency === $target && $rate > 0) {
                return round($companyAmount / $rate, 2);
            }

            if ($documentCurrency === $company && $rate > 1) {
                // Rare: same-currency purchase shouldn't store a FX rate, fall through to catalog rate.
            }
        }

        $multiplier = $this->exchangeRates->resolveMultiplier($company, $target);

        if ($multiplier === null || $multiplier <= 0) {
            return null;
        }

        return round($companyAmount * $multiplier, 2);
    }

    /**
     * Currency for the equivalence column: company currency when the document is foreign,
     * otherwise USD↔NIO opposite of company settings.
     */
    public function displayEquivalenceCurrency(Purchase $purchase): string
    {
        $documentCurrency = $this->documentCurrency($purchase);
        $companyCurrency = $this->companyCurrency();

        if ($documentCurrency !== $companyCurrency) {
            return $companyCurrency;
        }

        return $this->equivalenceCurrency();
    }

    public function equivalenceTotal(Purchase $purchase): ?float
    {
        $documentCurrency = $this->documentCurrency($purchase);
        $companyCurrency = $this->companyCurrency();

        if ($documentCurrency !== $companyCurrency) {
            return $this->companyTotal($purchase);
        }

        return $this->toEquivalenceAmount($this->companyTotal($purchase), $purchase);
    }

    /**
     * @return array{
     *     document_currency: string,
     *     document_symbol: string,
     *     document_total: float,
     *     company_currency: string,
     *     company_symbol: string,
     *     company_total: float,
     *     equivalence_currency: string,
     *     equivalence_symbol: string,
     *     equivalence_total: ?float,
     *     exchange_rate: float,
     *     is_foreign: bool
     * }
     */
    public function presentTotals(Purchase $purchase): array
    {
        $documentCurrency = $this->documentCurrency($purchase);
        $companyCurrency = $this->companyCurrency();
        $equivalenceCurrency = $this->displayEquivalenceCurrency($purchase);

        return [
            'document_currency' => $documentCurrency,
            'document_symbol' => $this->currencySymbol($documentCurrency),
            'document_total' => $this->documentTotal($purchase),
            'company_currency' => $companyCurrency,
            'company_symbol' => $this->companySymbol(),
            'company_total' => $this->companyTotal($purchase),
            'equivalence_currency' => $equivalenceCurrency,
            'equivalence_symbol' => $this->currencySymbol($equivalenceCurrency),
            'equivalence_total' => $this->equivalenceTotal($purchase),
            'exchange_rate' => (float) ($purchase->exchange_rate ?: 1),
            'is_foreign' => $documentCurrency !== $companyCurrency,
        ];
    }

    /**
     * @return array<string, float>
     */
    public function currentRatesToCompany(): array
    {
        $company = $this->companyCurrency();
        $rates = [];

        foreach ($this->supportedCurrencies() as $currency) {
            if ($currency === $company) {
                $rates[$currency] = 1.0;

                continue;
            }

            $rate = $this->exchangeRates->resolveMultiplier($currency, $company);
            if ($rate !== null && $rate > 0) {
                $rates[$currency] = round($rate, 6);
            }
        }

        return $rates;
    }

    public function toCompanyAmount(float $amount, float $exchangeRate): float
    {
        return round($amount * $exchangeRate, 2);
    }

    /**
     * @return array{unit_id: ?int, quantity: float, base_quantity: float}
     */
    public function resolveLineQuantity(Product $product, float $quantity, ?int $unitId): array
    {
        $resolvedUnitId = $unitId ?: $product->base_unit_id;
        $conversion = $resolvedUnitId && (int) $resolvedUnitId !== (int) $product->base_unit_id
            ? $product->unitConversions->firstWhere('unit_id', $resolvedUnitId)
            : null;

        if ($resolvedUnitId && (int) $resolvedUnitId !== (int) $product->base_unit_id && ! $conversion) {
            throw new RuntimeException('Unidad de medida no configurada para este producto.');
        }

        if ($conversion && ! $conversion->use_for_purchase) {
            throw new RuntimeException('La presentación seleccionada no está habilitada para compra.');
        }

        if ($conversion && ! $conversion->allow_fraction && abs($quantity - round($quantity)) > 0.000001) {
            throw new RuntimeException("La presentación {$conversion->unit?->abbreviation} solo admite cantidades enteras.");
        }

        $baseQuantity = $this->units->convertToBase($product, $quantity, $resolvedUnitId);

        return [
            'unit_id' => $resolvedUnitId,
            'quantity' => round($quantity, 4),
            'base_quantity' => $baseQuantity,
        ];
    }

    /**
     * @return list<array{id: int, abbreviation: string, name: string, factor_to_base: float}>
     */
    public function purchaseUnitsFor(Product $product): array
    {
        $product->loadMissing('baseUnit', 'unitConversions.unit');

        $base = $product->baseUnit ? collect([[
            'id' => $product->baseUnit->id,
            'abbreviation' => $product->baseUnit->abbreviation,
            'name' => $product->baseUnit->name,
            'factor_to_base' => 1.0,
            'is_default_purchase_unit' => ! $product->unitConversions->contains('is_default_purchase_unit', true),
        ]]) : collect();

        return $base->concat($product->unitConversions
            ->where('use_for_purchase', true)
            ->filter(fn ($conversion) => $conversion->unit !== null)
            ->map(fn ($conversion) => [
                'id' => $conversion->unit->id,
                'abbreviation' => $conversion->unit->abbreviation,
                'name' => $conversion->unit->name,
                'factor_to_base' => (float) $conversion->factor_to_base,
                'is_default_purchase_unit' => (bool) $conversion->is_default_purchase_unit,
            ]))
            ->sortByDesc('is_default_purchase_unit')
            ->values()
            ->all();
    }
}
