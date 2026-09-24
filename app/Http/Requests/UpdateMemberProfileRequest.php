<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('member')->check();
    }

    protected function prepareForValidation(): void
    {
        $phone = trim((string) $this->input('phone', ''));
        $address = trim((string) $this->input('address', ''));
        $gender = $this->input('gender');

        $this->merge([
            'full_name' => trim((string) $this->input('full_name', '')),
            'phone' => $phone === '' ? null : $phone,
            'address' => $address === '' ? null : $address,
            'gender' => in_array($gender, ['male', 'female', 'other'], true) ? $gender : null,
            'birth_date' => $this->input('birth_date') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'in:male,female,other'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'id_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => __('messages.full_name'),
            'phone' => __('messages.phone'),
            'gender' => __('messages.gender'),
            'birth_date' => __('messages.birth_date'),
            'address' => __('messages.address'),
            'photo' => __('messages.photo'),
            'id_document' => __('messages.id_document'),
        ];
    }
}
