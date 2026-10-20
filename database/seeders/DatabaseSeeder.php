<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LocationSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            HostelRoomSeeder::class,
            ResourceSeeder::class,
            ResourceRateSeeder::class,
            ReservationSeeder::class,
            HostelReservationSeeder::class,           
            HumanResourceSeeder::class,
            LecturerResourceSeeder::class,
            LectureFeeSeeder::class,
        ]);
    }
}
