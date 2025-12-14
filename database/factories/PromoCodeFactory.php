<?php

namespace Database\Factories;

use App\Models\PromoCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class PromoCodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PromoCode::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->word(),
            'type' => fake()->randomElement(['percentage', 'fixed']),
            'value' => fake()->randomFloat(0, 0, 9999),
            'minimum_amount' => fake()->randomFloat(0, 0, 9999),
            'maximum_discount' => fake()->randomFloat(0, 0, 999),
            'usage_limit_per_customer' => fake()->numberBetween(-100, 100),
            'usage_limit' => fake()->numberBetween(-10000, 10000),
            'usage_count' => fake()->numberBetween(-10000, 10000),
            'starts_at' => fake()->dateTime(),
            'ends_at' => fake()->dateTime(),
            'is_active' => fake()->boolean(),
        ];
    }
}
