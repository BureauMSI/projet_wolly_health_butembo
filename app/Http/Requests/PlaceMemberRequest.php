<?php

namespace App\Http\Requests;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $member = $this->route('member');

        return $member instanceof Member
            ? ($this->user()?->can('update', $member) ?? false)
            : false;
    }

    public function rules(): array
    {
        $memberId = $this->route('member') instanceof Member
            ? $this->route('member')->id
            : null;

        return [
            'sponsor_id' => ['required', 'exists:members,id', Rule::notIn([$memberId])],
            'placement_parent_id' => ['required', 'exists:members,id'],
            'placement_side' => ['required', 'in:left,right'],
        ];
    }

    public function attributes(): array
    {
        return [
            'sponsor_id' => __('messages.sponsor'),
            'placement_parent_id' => __('messages.placement_parent'),
            'placement_side' => __('messages.placement_side'),
        ];
    }

    public function messages(): array
    {
        return [
            'sponsor_id.not_in' => __('messages.cannot_sponsor_self'),
        ];
    }

    protected function getRedirectUrl()
    {
        $member = $this->route('member');

        if ($member instanceof Member) {
            return route('admin.placements.index', ['member' => $member->id]);
        }

        return parent::getRedirectUrl();
    }
}
