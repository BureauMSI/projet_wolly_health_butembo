<?php

namespace App\Services;

use App\Models\Member;
use App\Support\PlanConfig;
use Illuminate\Support\Str;

class UsernameGenerator
{
    public function __construct(private PlanConfig $plan) {}

    public function fromBase(string $base): string
    {
        $length = max(1, $this->plan->int('username_suffix_length', 4));
        $normalized = strtolower((string) preg_replace('/[^A-Za-z0-9._-]/', '', Str::ascii($base)));
        $normalized = $normalized !== '' ? $normalized : 'm';
        $alphabet = '0123456789abcdefghijklmnopqrstuvwxyz';

        do {
            $suffix = '';
            for ($i = 0; $i < $length; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $username = $normalized.$suffix;
        } while (Member::withTrashed()->where('username', $username)->exists());

        return $username;
    }
}
