<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    public function rules(): array
    {
        $isPercent = $this->input('benefit_type') === 'percent';

        return [
            'name' => ['required', 'string', 'max:255'],
            'benefit_type' => ['required', Rule::in(['pv', 'percent'])],
            'unit_price_usd' => ['required', 'numeric', 'min:0'],
            'member_unit_price_usd' => ['required', 'numeric', 'min:0'],
            'pv_per_tablet' => [$isPercent ? 'nullable' : 'required', 'numeric', 'min:0'],
            'box_price_usd' => ['required', 'numeric', 'min:0'],
            'member_box_price_usd' => ['required', 'numeric', 'min:0'],
            'box_pv' => [$isPercent ? 'nullable' : 'required', 'numeric', 'min:0'],
            'commission_percent' => [$isPercent ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
