<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceType;
use Illuminate\Database\Seeder;

/**
 * Hostel rooms are ordinary `resources` under category "Hostel Room".
 * Interns copying this for other modules: do not invent a second rooms table.
 * Room category on the hostel form is a ResourceType (Single / Double).
 */
class HostelRoomSeeder extends Seeder
{
    public function run(): void
    {
        $category = ResourceCategory::query()->firstOrCreate(
            ['name' => config('hostel.category_name', 'Hostel Room')]
        );

        $single = ResourceType::query()->firstOrCreate(
            ['resource_category_id' => $category->id, 'name' => 'Single'],
            ['description' => 'One occupant']
        );
        $double = ResourceType::query()->firstOrCreate(
            ['resource_category_id' => $category->id, 'name' => 'Double'],
            ['description' => 'Two occupants']
        );

        $welisara = Location::query()->firstOrCreate(['name' => 'Welisara']);
        $moratuwa = Location::query()->firstOrCreate(['name' => 'Moratuwa']);

        $rooms = [
            ['serial_number' => 'HR-WEL-S-101', 'name_model' => 'Welisara Single 101', 'type' => $single, 'location' => $welisara],
            ['serial_number' => 'HR-WEL-S-102', 'name_model' => 'Welisara Single 102', 'type' => $single, 'location' => $welisara],
            ['serial_number' => 'HR-WEL-D-201', 'name_model' => 'Welisara Double 201', 'type' => $double, 'location' => $welisara],
            ['serial_number' => 'HR-MOR-S-101', 'name_model' => 'Moratuwa Single 101', 'type' => $single, 'location' => $moratuwa],
        ];

        foreach ($rooms as $room) {
            Resource::query()->firstOrCreate(
                ['serial_number' => $room['serial_number']],
                [
                    'resource_type_id' => $room['type']->id,
                    'location_id' => $room['location']->id,
                    'name_model' => $room['name_model'],
                    'status' => 'active',
                ]
            );
        }
    }
}
