<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'concept' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'lines.*.detail' => ['nullable', 'string', 'max:255'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $index => $line) {
                $debit = round((float) ($line['debit'] ?? 0), 2);
                $credit = round((float) ($line['credit'] ?? 0), 2);

                if ($debit > 0 && $credit > 0) {
                    $validator->errors()->add("lines.{$index}.debit", 'Una línea no puede tener Debe y Haber a la vez.');
                }

                if ($debit <= 0 && $credit <= 0) {
                    $validator->errors()->add("lines.{$index}.debit", 'Indique un monto en Debe o en Haber.');
                }

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                $validator->errors()->add('lines', 'El asiento está desbalanceado: Debe (' . number_format($totalDebit, 2) . ') debe ser igual a Haber (' . number_format($totalCredit, 2) . ').');
            }
        });
    }
}
