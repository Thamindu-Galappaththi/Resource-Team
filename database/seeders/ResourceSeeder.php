<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\ResourceType;
use App\Models\Resource;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    /**
     * Seed resources with different locations.
     */
    public function run(): void
    {
        $locations = Location::all();
        $types = ResourceType::with('category')->get();

        $resources = [
            [
                'name_model' => 'Default Available Resource One',
                'type_index' => 0,
                'location_index' => 0,
                'serial_number' => 'RES-DEFAULT-001',
            ],
            [
                'name_model' => 'Default Available Resource Two',
                'type_index' => 1,
                'location_index' => 1,
                'serial_number' => 'RES-DEFAULT-002',
            ],
            [
                'name_model' => 'Default Available Resource Three',
                'type_index' => 2,
                'location_index' => 2,
                'serial_number' => 'RES-DEFAULT-003',
            ],
            [
                'name_model' => 'Available Resource 1',
                'type_index' => 0,
                'location_index' => 0,
                'serial_number' => 'RES-001',
            ],
            [
                'name_model' => 'Available Resource 2',
                'type_index' => 1,
                'location_index' => 1,
                'serial_number' => 'RES-002',
            ],
            [
                'name_model' => 'Available Resource 3',
                'type_index' => 2,
                'location_index' => 2,
                'serial_number' => 'RES-003',
            ],
        ];

        foreach ($resources as $resource) {
            $type = $types[$resource['type_index']] ?? $types->first();
            $location = $locations[$resource['location_index']] ?? $locations->first();

            Resource::firstOrCreate(
                [
                    'resource_type_id' => $type->id,
                    'location_id' => $location->id,
                    'name_model' => $resource['name_model'],
                ],
                [
                    'serial_number' => $resource['serial_number'],
                    'status' => 'active',
                ]
            );
        }

        // Create default addon resources for each location
        $defaultAddonNames = ['Default Add-on 1', 'Default Add-on 2', 'Default Add-on 3'];
        foreach ($locations as $index => $location) {
            foreach ($defaultAddonNames as $addonIndex => $addonName) {
                Resource::firstOrCreate(
                    [
                        'resource_type_id' => $types[$addonIndex]->id,
                        'location_id' => $location->id,
                        'name_model' => $addonName,
                    ],
                    [
                        'serial_number' => 'ADDON-DEFAULT-' . $location->id . '-' . ($addonIndex + 1),
                        'status' => 'active',
                    ]
                );
            }
        }

        // Create regular addon resources for each location
        $addonNames = ['Addon 1', 'Addon 2', 'Addon 3'];
        foreach ($locations as $index => $location) {
            foreach ($addonNames as $addonIndex => $addonName) {
                Resource::firstOrCreate(
                    [
                        'resource_type_id' => $types[$addonIndex]->id,
                        'location_id' => $location->id,
                        'name_model' => $addonName,
                    ],
                    [
                        'serial_number' => 'ADDON-' . $location->id . '-' . ($addonIndex + 1),
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
