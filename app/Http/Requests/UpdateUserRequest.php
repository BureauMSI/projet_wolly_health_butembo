<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            ? ($this->user()?->can('update', $user) ?? false)
            : false;
    }

    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');
        $branchRoles = [User::ROLE_MANAGER, User::ROLE_CASHIER, User::ROLE_ACCOUNTANT];

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($target->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target->id)],
            'password' => ['nullable', 'string', 'min:8'],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var User $target */
            $target = $this->route('user');
            if (! $target instanceof User || ! $target->isAdmin()) {
                return;
            }

            $activeAdmins = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->where('is_active', true)
                ->count();

            $demoting = $this->input('role') !== User::ROLE_ADMIN;
            $deactivating = ! $this->boolean('is_active');

            if ($activeAdmins <= 1 && ($demoting || $deactivating)) {
                $validator->errors()->add('role', __('messages.last_admin_guard'));
            }
        });
    }
}
