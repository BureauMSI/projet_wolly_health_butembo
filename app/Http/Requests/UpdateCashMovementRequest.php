<?php

namespace App\Http\Requests;

use App\Models\CashMovement;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $movement = $this->route('movement');

        return $movement instanceof CashMovement && ($this->user()?->can('update', $movement) ?? false);
    }

    public function rules(): array
    {
        return (new StoreCashMovementRequest)->rules();
    }
}
