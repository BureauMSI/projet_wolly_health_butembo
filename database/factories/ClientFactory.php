<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '099'.fake()->numerify('######'),
            'referrer_member_id' => Member::factory(),
            'accumulated_pv' => 0,
        ];
    }
}
