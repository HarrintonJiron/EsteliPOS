<?php

namespace App\Services;

use App\Models\Setting;

class InvoiceTaxDisplayService
{
    public const MODE_GENERAL = 'general';

    public const MODE_EXEMPT = 'exempt';

    public const MODE_HIDE = 'hide';

    public const SETTING_KEY = 'invoice_tax_display_mode';

    /** @var array<string,string> */
    private const OPTIONS = [
        self::MODE_GENERAL => 'IVA General (15%)',
        self::MODE_EXEMPT => 'Exento de IVA (0%)',
        self::MODE_HIDE => 'Ocultar impuesto en la factura',
    ];

    private ?string $resolvedMode = null;

    /** @return array<string,string> */
    public static function options(): array
    {
        return self::OPTIONS;
    }

    public function mode(): string
    {
        if ($this->resolvedMode !== null) {
            return $this->resolvedMode;
        }

        $mode = (string) Setting::get(self::SETTING_KEY, self::MODE_GENERAL);

        if (! array_key_exists($mode, self::OPTIONS)) {
            $mode = self::MODE_GENERAL;
        }

        return $this->resolvedMode = $mode;
    }

    public function showsTaxBreakdown(): bool
    {
        return $this->mode() !== self::MODE_HIDE;
    }

    public function showsTaxInTotals(float $taxTotal): bool
    {
        if ($this->mode() === self::MODE_HIDE) {
            return false;
        }

        if ($this->mode() === self::MODE_EXEMPT && $taxTotal == 0) {
            return false;
        }

        return true;
    }

    public function isExemptMode(): bool
    {
        return $this->mode() === self::MODE_EXEMPT;
    }

    public function taxLabel(float $taxRate): string
    {
        if ($this->isExemptMode()) {
            return 'Exento de IVA';
        }

        return 'IVA ('.number_format($taxRate * 100, 2).'%)';
    }

    public function displayTaxAmount(float $taxAmount): float
    {
        return $this->isExemptMode() ? 0.0 : $taxAmount;
    }

    public function displayTaxRate(float $taxRate): float
    {
        return $this->isExemptMode() ? 0.0 : $taxRate;
    }

    public function showsLineTax(float $lineTaxRate): bool
    {
        if (! $this->showsTaxBreakdown()) {
            return false;
        }

        return ! $this->isExemptMode() && $lineTaxRate > 0;
    }
}
