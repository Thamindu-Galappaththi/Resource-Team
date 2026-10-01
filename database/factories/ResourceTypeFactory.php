<?php

namespace Database\Factories;

use App\Models\ResourceCategory;
use App\Models\ResourceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResourceType>
 */
class ResourceTypeFactory extends Factory
{
    protected $model = ResourceType::class;

    public function definition(): array
    {
        return [
            'resource_category_id' => ResourceCategory::factory(),
            'name' => fake()->unique()->words(2, true).' Type',
            'description' => fake()->optional()->sentence(),
        ];
    }
}
