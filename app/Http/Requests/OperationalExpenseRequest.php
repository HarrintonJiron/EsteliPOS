<?php

namespace App\Http\Requests;

use App\Models\OperationalExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OperationalExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->isActive()) {
            return false;
        }

        $routeName = (string) $this->route()?->getName();

        if ($user->isAdmin()) {
            return true;
        }

        return match (true) {
            str_contains($routeName, '.store') => $user->hasPermission('reparaciones.create_expenses'),
            str_contains($routeName, '.update') => $user->hasPermission('reparaciones.edit_expenses'),
            default => false,
        };
    }

    public function rules(): array
    {
        return [
            'caja_session_id' => ['nullable', 'integer', 'exists:caja_sessions,id'],
            'repair_order_id' => ['nullable', 'integer', 'exists:repair_orders,id'],
            'account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', Rule::in(['cash', 'transfer', 'card', 'other'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in([
                OperationalExpense::STATUS_DRAFT,
                OperationalExpense::STATUS_REGISTERED,
                OperationalExpense::STATUS_CANCELLED,
            ])],
        ];
    }
}