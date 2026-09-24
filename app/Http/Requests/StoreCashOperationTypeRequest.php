<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashOperationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:120'],
            'direction' => ['required', 'in:in,out,both'],
        ];
    }
}
