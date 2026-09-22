<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('reservations')->insert([

            [
                'reservation_date' => '2026-09-22',
                'title' => 'Team Meeting',
                'description' => 'Development team meeting',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'reservation_date' => '2026-09-22',
                'title' => 'Client Meeting',
                'description' => 'Meeting with the client',
                'start_time' => '14:00:00',
                'end_time' => '15:30:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'reservation_date' => '2026-09-25',
                'title' => 'Laravel Training',
                'description' => 'Laravel development training',
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'reservation_date' => '2026-09-30',
                'title' => 'Project Discussion',
                'description' => 'Discuss the project progress',
                'start_time' => '13:00:00',
                'end_time' => '14:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'reservation_date' => '2026-09-01',
                'title' => 'QA Discussion',
                'description' => 'Discuss the current issues',
                'start_time' => '13:00:00',
                'end_time' => '14:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}
