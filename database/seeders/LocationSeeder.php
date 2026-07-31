<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Populates the "locations" lookup table (used by the Location
     * dropdown on Tab 3) with the initial set of values. Uses
     * firstOrCreate so re-running this seeder is safe — it won't
     * create duplicates if a location with that name already exists.
     */
    public function run(): void
    {
        $locations = [
            'Welisara',
            'Moratuwa',
            'Peradeniya',
        ];

        foreach ($locations as $name) {
            Location::firstOrCreate(['name' => $name]);
        }
    }
}