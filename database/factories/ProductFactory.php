<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;

class ProductFactory extends BaseFactory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->localized(fn (): string => fake()->sentence(2, 3)),
            'amount' => fake()->randomFloat(2, 10, 100),
            'sale_amount' => fake()->optional(0.3, null)->randomFloat(2, 5, 50),
            'sale_end_date' => fake()->optional(0.3, null)->dateTimeBetween('now', '+1 month'),
            'slug' => fake()->unique()->slug(),
            'description' => $this->localized(
                fn (): string => fake()->sentence(18, 28)
            ),
            'is_active' => fake()->boolean(),
            'category_id' => Category::factory(),
        ];
    }
}
