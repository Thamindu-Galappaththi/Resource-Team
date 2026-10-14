<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceType;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $welisara = Location::query()->firstOrCreate(['name' => 'Welisara']);
        $moratuwa = Location::query()->firstOrCreate(['name' => 'Moratuwa']);
        $peradeniya = Location::query()->firstOrCreate(['name' => 'Peradeniya']);

        $it = ResourceCategory::query()->firstOrCreate(['name' => 'IT Equipment']);
        $space = ResourceCategory::query()->firstOrCreate(['name' => 'Physical Space']);
        $av = ResourceCategory::query()->firstOrCreate(['name' => 'Audio Visual']);

        $laptop = ResourceType::query()->firstOrCreate(
            ['resource_category_id' => $it->id, 'name' => 'Laptop'],
            ['description' => 'Portable computer']
        );
        $projector = ResourceType::query()->firstOrCreate(
            ['resource_category_id' => $av->id, 'name' => 'Projector'],
            ['description' => 'Classroom projector']
        );
        $hall = ResourceType::query()->firstOrCreate(
            ['resource_category_id' => $space->id, 'name' => 'Lecture Hall'],
            ['description' => 'Standard teaching hall']
        );
        $meeting = ResourceType::query()->firstOrCreate(
            ['resource_category_id' => $space->id, 'name' => 'Meeting Room'],
            ['description' => 'Small meeting space']
        );
        $lab = ResourceType::query()->firstOrCreate(
            ['resource_category_id' => $space->id, 'name' => 'Computer Lab'],
            ['description' => 'Teaching lab']
        );

        $items = [
            ['serial_number' => 'LT-WEL-001', 'name_model' => 'Dell Latitude 5540', 'type' => $laptop, 'location' => $welisara, 'status' => 'active'],
            ['serial_number' => 'LT-WEL-002', 'name_model' => 'HP EliteBook 840', 'type' => $laptop, 'location' => $welisara, 'status' => 'active'],
            ['serial_number' => 'LT-MOR-001', 'name_model' => 'Lenovo ThinkPad T14', 'type' => $laptop, 'location' => $moratuwa, 'status' => 'under_maintenance'],
            ['serial_number' => 'LT-PER-001', 'name_model' => 'Dell Latitude 5440', 'type' => $laptop, 'location' => $peradeniya, 'status' => 'active'],
            ['serial_number' => 'PJ-WEL-001', 'name_model' => 'Epson EB-X51', 'type' => $projector, 'location' => $welisara, 'status' => 'active'],
            ['serial_number' => 'PJ-MOR-001', 'name_model' => 'BenQ MX560', 'type' => $projector, 'location' => $moratuwa, 'status' => 'active'],
            ['serial_number' => 'PJ-PER-001', 'name_model' => 'Epson EB-E01', 'type' => $projector, 'location' => $peradeniya, 'status' => 'inactive'],
            ['serial_number' => 'LH-WEL-A-001', 'name_model' => 'Hall A', 'type' => $hall, 'location' => $welisara, 'status' => 'active'],
            ['serial_number' => 'LH-WEL-B-002', 'name_model' => 'Hall B', 'type' => $hall, 'location' => $welisara, 'status' => 'active'],
            ['serial_number' => 'LH-MOR-A-001', 'name_model' => 'Moratuwa Hall 1', 'type' => $hall, 'location' => $moratuwa, 'status' => 'active'],
            ['serial_number' => 'MR-WEL-101', 'name_model' => 'Meeting Room 101', 'type' => $meeting, 'location' => $welisara, 'status' => 'active'],
            ['serial_number' => 'MR-MOR-201', 'name_model' => 'Meeting Room 201', 'type' => $meeting, 'location' => $moratuwa, 'status' => 'active'],
            ['serial_number' => 'LAB-WEL-03', 'name_model' => 'Computer Lab 03', 'type' => $lab, 'location' => $welisara, 'status' => 'active'],
            ['serial_number' => 'LAB-PER-01', 'name_model' => 'Computer Lab Peradeniya', 'type' => $lab, 'location' => $peradeniya, 'status' => 'under_maintenance'],
        ];

        foreach ($items as $item) {
            Resource::query()->firstOrCreate(
                ['serial_number' => $item['serial_number']],
                [
                    'resource_type_id' => $item['type']->id,
                    'location_id' => $item['location']->id,
                    'name_model' => $item['name_model'],
                    'status' => $item['status'],
                ]
            );
        }
    }
}
