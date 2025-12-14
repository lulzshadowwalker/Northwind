<?php

namespace Database\Factories;

class FaqFactory extends BaseFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question' => $this->localized(fn (): string => fake()->sentence()),
            'answer' => $this->localized(fn (): string => fake()->paragraph()),
        ];
    }
}
