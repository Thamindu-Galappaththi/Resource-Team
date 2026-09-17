<?php

namespace Database\Seeders;

use App\Models\ResourceCategory;
use Illuminate\Database\Seeder;

class ResourceCategorySeeder extends Seeder
{
    /**
     * Seed resource categories for the system.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Default Category One'],
            ['name' => 'Default Category Two'],
            ['name' => 'Default Category Three'],
            ['name' => 'Category 1'],
            ['name' => 'Category 2'],
            ['name' => 'Category 3'],
        ];

        foreach ($categories as $category) {
            ResourceCategory::firstOrCreate($category);
        }
    }
}
