<?php

namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sale = $this->route('sale');

        return $sale instanceof Sale && ($this->user()?->can('update', $sale) ?? false);
    }

    public function rules(): array
    {
        return (new StoreSaleRequest)->rules();
    }
}
