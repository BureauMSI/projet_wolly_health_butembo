<?php

namespace App\Services;

use App\Models\Member;

readonly class MemberRegistration
{
    public function __construct(
        public Member $member,
        public string $plainPassword,
    ) {}
}
