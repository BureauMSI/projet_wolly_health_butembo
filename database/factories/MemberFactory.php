<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'member_code' => 'HH-'.fake()->unique()->numerify('#####'),
            'full_name' => fake()->name(),
            'phone' => '099'.fake()->numerify('######'),
            'username' => fake()->unique()->userName(),
            'password' => 'password',
            'locale' => 'fr',
            'status' => 'active',
            'joined_at' => now(),
        ];
    }
}
