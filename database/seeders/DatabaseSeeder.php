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
            ReservationSeeder::class,
            HostelReservationSeeder::class,
            LectureFeeSeeder::class,
            HumanResourceSeeder::class,
        ]);
    }
}
