<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        $branchRoles = [User::ROLE_MANAGER, User::ROLE_CASHIER, User::ROLE_ACCOUNTANT];

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_MANAGER,
                User::ROLE_CASHIER,
                User::ROLE_ACCOUNTANT,
            ])],
            'branch_id' => [
                Rule::requiredIf(fn () => in_array($this->input('role'), $branchRoles, true)),
                'nullable',
                'exists:branches,id',
            ],
            'locale' => ['required', 'in:fr,sw'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('role') === User::ROLE_ADMIN) {
            $this->merge(['branch_id' => null]);
        }
    }
}
