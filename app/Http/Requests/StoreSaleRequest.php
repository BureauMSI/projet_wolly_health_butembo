<?php

namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Sale::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'exists:branches,id'],
            'buyer_type' => ['required', 'in:member,client'],
            'benefit_mode' => ['required', 'in:pv,percent'],
            'member_id' => ['required_if:buyer_type,member', 'nullable', 'exists:members,id'],
            'client_id' => ['required_if:buyer_type,client', 'nullable', 'exists:clients,id'],
            'currency_code' => ['required', 'exists:currencies,code'],
            'promo_usd' => ['nullable', 'numeric', 'min:0'],
            'discount_usd' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.packing' => ['required', 'in:tablet,box'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
