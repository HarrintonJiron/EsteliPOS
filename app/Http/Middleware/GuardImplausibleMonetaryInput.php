<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class GuardImplausibleMonetaryInput
{
    public const CONFIRMATION_THRESHOLD = 1_000_000;

    public const ABSOLUTE_LIMIT = 9_999_999.99;

    /** @var list<string> */
    private const MONETARY_FIELDS = [
        'amount', 'amount_received', 'advance_payment', 'base_salary', 'credit', 'credit_limit',
        'debit', 'discount_amount', 'hourly_rate', 'labor_cost', 'monthly_payment', 'opening_amount',
        'price', 'purchase_price', 'sale_price', 'salary', 'unit_price',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            $suspicious = $this->findSuspiciousValues($request->all());

            if ($suspicious['blocked']) {
                throw ValidationException::withMessages([
                    $suspicious['blocked']['path'] => sprintf(
                        'El valor %s excede el límite seguro de C$ %s. Revisa la cantidad escrita.',
                        number_format($suspicious['blocked']['value'], 2),
                        number_format(self::ABSOLUTE_LIMIT, 2),
                    ),
                ]);
            }

            if ($suspicious['confirmation'] && ! $request->boolean('large_amount_confirmed')) {
                throw ValidationException::withMessages([
                    $suspicious['confirmation']['path'] => sprintf(
                        'El monto de C$ %s es inusualmente alto. Confírmalo expresamente antes de guardar.',
                        number_format($suspicious['confirmation']['value'], 2),
                    ),
                ]);
            }
        }

        return $next($request);
    }

    /** @return array{blocked: array{path: string, value: float}|null, confirmation: array{path: string, value: float}|null} */
    private function findSuspiciousValues(array $input): array
    {
        if (isset($input['items']) && is_string($input['items'])) {
            $decoded = json_decode($input['items'], true);
            if (is_array($decoded)) {
                $input['items'] = $decoded;
            }
        }

        $confirmation = null;
        foreach (Arr::dot($input) as $path => $rawValue) {
            $field = str((string) $path)->afterLast('.')->toString();
            if (! in_array($field, self::MONETARY_FIELDS, true) || ! is_scalar($rawValue)) {
                continue;
            }

            $normalized = str_replace([',', ' '], '', (string) $rawValue);
            if (! is_numeric($normalized)) {
                continue;
            }

            $value = abs((float) $normalized);
            if ($value > self::ABSOLUTE_LIMIT) {
                return ['blocked' => ['path' => $path, 'value' => $value], 'confirmation' => $confirmation];
            }

            if ($value >= self::CONFIRMATION_THRESHOLD && ($confirmation === null || $value > $confirmation['value'])) {
                $confirmation = ['path' => $path, 'value' => $value];
            }
        }

        return ['blocked' => null, 'confirmation' => $confirmation];
    }
}
