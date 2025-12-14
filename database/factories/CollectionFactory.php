<?php

namespace Database\Factories;

use App\Models\Collection;

class CollectionFactory extends BaseFactory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Collection::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->localized(fn (): string => fake()->word()),
            'slug' => fake()->unique()->slug(),
            'description' => $this->localized(
                fn (): string => fake()->sentence()
            ),
            'is_active' => fake()->boolean(),
        ];
    }
}
