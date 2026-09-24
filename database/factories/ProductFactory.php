<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('PR-###')),
            'name' => fake()->words(2, true),
            'benefit_type' => 'pv',
            'unit_price_usd' => 5,
            'member_unit_price_usd' => 5,
            'pv_per_tablet' => 2,
            'box_price_usd' => 40,
            'member_box_price_usd' => 40,
            'box_pv' => 20,
            'commission_percent' => 0,
            'is_active' => true,
        ];
    }

    public function percent(float $percent = 10): static
    {
        return $this->state(fn () => [
            'benefit_type' => 'percent',
            'pv_per_tablet' => 0,
            'box_pv' => 0,
            'commission_percent' => $percent,
        ]);
    }
}
