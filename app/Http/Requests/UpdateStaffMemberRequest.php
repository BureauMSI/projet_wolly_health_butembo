<?php

namespace App\Http\Requests;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $member = $this->route('member');

        return $member instanceof Member && ($this->user()?->can('update', $member) ?? false);
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
            'status' => ['required', 'in:active,inactive'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'id_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }
}
