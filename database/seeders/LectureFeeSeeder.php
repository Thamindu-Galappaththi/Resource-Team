<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LectureFeeSeeder extends Seeder
{
    public function run(): void
    {
        $lecturers = [
            [
                'name' => 'Ishara Perera',
                'service_id' => 'LECT-001',
                'slt_employee' => 0,
                'nic' => 'TEST001',
                'email' => 'ishara.perera@test.local',
                'phone' => '0710000001',
                'location' => 'Welisara',
                'password' => Hash::make('password'),
                'user_role' => 'lecturer',
                'role_id' => null,
                'designation' => 'Lecturer',
                'user_type' => 'lecturer',
                'user_profile' => null,
                'is_active' => 1,
            ],
            [
                'name' => 'Nimal Fernando',
                'service_id' => 'LECT-002',
                'slt_employee' => 0,
                'nic' => 'TEST002',
                'email' => 'nimal.fernando@test.local',
                'phone' => '0710000002',
                'location' => 'Welisara',
                'password' => Hash::make('password'),
                'user_role' => 'lecturer',
                'role_id' => null,
                'designation' => 'Senior Lecturer',
                'user_type' => 'lecturer',
                'user_profile' => null,
                'is_active' => 1,
            ],
            [
                'name' => 'Kavindu Silva',
                'service_id' => 'LECT-003',
                'slt_employee' => 0,
                'nic' => 'TEST003',
                'email' => 'kavindu.silva@test.local',
                'phone' => '0710000003',
                'location' => 'Welisara',
                'password' => Hash::make('password'),
                'user_role' => 'lecturer',
                'role_id' => null,
                'designation' => 'Lecturer',
                'user_type' => 'lecturer',
                'user_profile' => null,
                'is_active' => 1,
            ],
        ];

        foreach ($lecturers as $lecturer) {
            User::updateOrCreate(
                ['email' => $lecturer['email']],
                $lecturer
            );
        }
    }
}