<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductQuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductQuestion::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'question' => fake()->word(),
            'email' => fake()->safeEmail(),
            'answer' => fake()->word(),
            'product_id' => Product::factory(),
            'customer_id' => Customer::factory(),
        ];
    }
}
