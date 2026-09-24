<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\Member;
use App\Support\PlanConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Member::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $username = $this->sanitizeUsername((string) $this->input('username', ''));

        if ($username === '') {
            $username = $this->sanitizeUsername((string) $this->input('full_name', ''));
        }

        $this->merge([
            'username' => $username,
            'locale' => $this->input('locale') ?: 'fr',
            'membership_type' => $this->input('membership_type') === 'indirect' ? 'indirect' : 'direct',
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:2', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/'],
            'password' => ['nullable', 'string', Password::min(8)],
            'registration_branch_id' => ['nullable', 'exists:branches,id'],
            'locale' => ['required', 'in:fr,sw'],
            'membership_type' => ['required', 'in:direct,indirect'],
            'source_client_id' => ['required_if:membership_type,indirect', 'nullable', 'exists:clients,id'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'id_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }

    private function sanitizeUsername(string $value): string
    {
        return strtolower((string) preg_replace('/[^A-Za-z0-9._-]/', '', Str::ascii($value)));
    }

    public function attributes(): array
    {
        return [
            'full_name' => __('messages.full_name'),
            'username' => __('messages.username'),
            'password' => __('messages.password'),
            'photo' => __('messages.photo'),
            'id_document' => __('messages.id_document'),
            'source_client_id' => __('messages.source_client'),
            'membership_type' => __('messages.membership_type'),
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => __('messages.password_min'),
            'username.min' => __('messages.username_min'),
            'source_client_id.required_if' => __('messages.source_client_required'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('membership_type') !== 'indirect' || ! filled($this->input('source_client_id'))) {
                return;
            }

            $threshold = (float) app(PlanConfig::class)->decimal('membership_pv_threshold');
            $client = Client::query()->find($this->input('source_client_id'));
            if ($client !== null && $client->canFundIndirectMembership($threshold)) {
                return;
            }

            $validator->errors()->add(
                'source_client_id',
                $client?->converted_member_id
                    ? __('messages.client_pv_already_used')
                    : __('messages.client_pv_not_enough'),
            );
        });
    }
}
