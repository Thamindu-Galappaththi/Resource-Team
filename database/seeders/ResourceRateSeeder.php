<?php

namespace Database\Seeders;

use App\Models\ResourceCategory;
use App\Models\ResourceRate;
use App\Models\ResourceType;
use Illuminate\Database\Seeder;

class ResourceRateSeeder extends Seeder
{
    public function run(): void
    {
        $space = ResourceCategory::query()->firstOrCreate(['name' => 'Physical Space']);
        $av = ResourceCategory::query()->firstOrCreate(['name' => 'Audio Visual']);
        $it = ResourceCategory::query()->firstOrCreate(['name' => 'IT Equipment']);

        $catalog = [
            ['name' => 'Lecture Hall', 'category' => $space, 'base_fee' => 5000, 'duration' => 2, 'extra' => 2500],
            ['name' => 'Lecture Hall (small)', 'category' => $space, 'base_fee' => 3000, 'duration' => 2, 'extra' => 1500],
            ['name' => 'Computer Lab', 'category' => $space, 'base_fee' => 4000, 'duration' => 2, 'extra' => 2000],
            ['name' => 'Auditorium', 'category' => $space, 'base_fee' => 15000, 'duration' => 3, 'extra' => 6000],
            ['name' => 'Meeting Room', 'category' => $space, 'base_fee' => 2000, 'duration' => 1, 'extra' => 1500],
            ['name' => 'Seminar Room', 'category' => $space, 'base_fee' => 2500, 'duration' => 2, 'extra' => 1200],
            ['name' => 'Projector', 'category' => $av, 'base_fee' => 1000, 'duration' => 1, 'extra' => 500],
            ['name' => 'Sports Facility', 'category' => $space, 'base_fee' => 3500, 'duration' => 2, 'extra' => 1800],
        ];

        foreach ($catalog as $entry) {
            $type = ResourceType::query()->firstOrCreate(
                ['resource_category_id' => $entry['category']->id, 'name' => $entry['name']],
                ['description' => 'Resource type for reservations']
            );
            ResourceRate::query()->updateOrCreate(
                ['resource_type_id' => $type->id, 'resource_id' => null, 'effective_from' => '2026-01-01'],
                ['base_fee' => $entry['base_fee'], 'base_duration_hours' => $entry['duration'], 'extra_hour_rate' => $entry['extra'], 'tax_percent' => 0, 'effective_to' => null, 'is_active' => true]
            );
        }
    }
}
