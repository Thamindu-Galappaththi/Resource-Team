<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Resource;
use App\Models\ResourceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resource>
 */
class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        return [
            'resource_type_id' => ResourceType::factory(),
            'location_id' => Location::factory(),
            'name_model' => fake()->words(2, true).' '.fake()->randomNumber(3),
            'serial_number' => strtoupper(fake()->unique()->bothify('??-###-???')),
            'status' => 'active',
        ];
    }
}
