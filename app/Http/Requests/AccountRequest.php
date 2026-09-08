<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $accountId = $this->route('account')?->id ?? $this->route('account');

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('accounts', 'code')->ignore($accountId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in(array_keys(Account::TYPES))],
            'nature' => ['required', Rule::in(['debit', 'credit'])],
            'parent_id' => ['nullable', 'exists:accounts,id'],
            'is_postable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
