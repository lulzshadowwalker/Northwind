<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
abstract class BaseFactory extends Factory
{
    protected function localized(callable $fn): array
    {
        $locales = config('app.supported_locales');

        return Arr::collapse(
            array_map(fn ($locale) => [$locale => $fn()], $locales)
        );
    }
}
