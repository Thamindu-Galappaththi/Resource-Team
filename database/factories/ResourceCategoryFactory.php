<?php

namespace Database\Factories;

use App\Models\ResourceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResourceCategory>
 */
class ResourceCategoryFactory extends Factory
{
    protected $model = ResourceCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' Category',
        ];
    }
}
