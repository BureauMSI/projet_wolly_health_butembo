<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            ? ($this->user()?->can('update', $product) ?? false)
            : false;
    }

    public function rules(): array
    {
        $product = $this->route('product');
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
