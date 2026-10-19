<?php

namespace Database\Seeders;

use App\Models\LecturerProfile;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceType;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LecturerResourceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $category = ResourceCategory::firstOrCreate([
                'name' => 'Human Resource',
            ]);

            $type = ResourceType::firstOrCreate(
                [
                    'resource_category_id' => $category->id,
                    'name' => 'Lecturer',
                ],
                [
                    'description' => 'Lecturers registered as Human Resources',
                ]
            );

            $location = Location::first();

            if (!$location) {
                throw new \RuntimeException(
                    'No locations found. Create a location before seeding lecturers.'
                );
            }

            $lecturers = [
                [
                    'name' => 'Ishara Perera',
                    'service_id' => 'LECT-001',
                    'nic' => 'TEST001',
                    'email' => 'ishara.perera@test.local',
                    'phone' => '0710000001',
                    'designation' => 'Lecturer',
                ],
                [
                    'name' => 'Nimal Fernando',
                    'service_id' => 'LECT-002',
                    'nic' => 'TEST002',
                    'email' => 'nimal.fernando@test.local',
                    'phone' => '0710000002',
                    'designation' => 'Senior Lecturer',
                ],
                [
                    'name' => 'Kavindu Silva',
                    'service_id' => 'LECT-003',
                    'nic' => 'TEST003',
                    'email' => 'kavindu.silva@test.local',
                    'phone' => '0710000003',
                    'designation' => 'Lecturer',
                ],
            ];

            foreach ($lecturers as $lecturer) {
                $profile = LecturerProfile::where(
                    'service_id',
                    $lecturer['service_id']
                )->first();

                if ($profile) {
                    $resource = $profile->resource;
                } else {
                    $resource = Resource::create([
                        'resource_type_id' => $type->id,
                        'location_id' => $location->id,
                        'resource_owner' => null,
                        'name_model' => $lecturer['name'],
                        'serial_number' => $lecturer['service_id'],
                        'status' => 'active',
                        'is_deleted' => false,
                    ]);
                }

                LecturerProfile::updateOrCreate(
                    ['service_id' => $lecturer['service_id']],
                    array_merge($lecturer, [
                        'resource_id' => $resource->id,
                        'slt_employee' => false,
                        'is_active' => true,
                    ])
                );
            }
        });
    }
}