<?php

namespace App\Http\Requests;

use App\Models\CashMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CashMovement::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'exists:branches,id'],
            'direction' => ['required', 'in:in,out'],
            'category' => ['required', 'string', Rule::exists('cash_operation_types', 'code')],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['required', 'exists:currencies,code'],
            'description' => ['nullable', 'required_if:category,other,other_income,other_expense', 'string', 'max:500'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
