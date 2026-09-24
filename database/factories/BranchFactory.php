<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'institution_id' => Institution::query()->value('id'),
            'name' => fake()->city(),
            'code' => strtoupper(fake()->unique()->lexify('??#')),
            'is_active' => true,
        ];
    }
}
