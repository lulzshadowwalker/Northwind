<?php

namespace Database\Factories;

use App\Models\Category;

class CategoryFactory extends BaseFactory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->localized(
                fn (): string => fake()->unique()->sentence(2, 3)
            ),
            'slug' => fake()->unique()->slug(),
        ];
    }
}
