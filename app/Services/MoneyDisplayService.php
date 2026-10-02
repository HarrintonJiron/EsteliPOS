<?php

namespace App\Services;

class MoneyDisplayService
{
    public const BASE_CURRENCY = 'USD';

    public function format(float|int|string|null $amount, int $decimals = 2, mixed ...$ignoredFormatArguments): string
    {
        $symbol = app(CompanySettingsService::class)->get()['currency_symbol'] ?? '$';

        return $symbol.' '.number_format((float) ($amount ?? 0), $decimals);
    }
}
