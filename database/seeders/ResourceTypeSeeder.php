<?php

namespace Database\Seeders;

use App\Models\ResourceCategory;
use App\Models\ResourceType;
use Illuminate\Database\Seeder;

class ResourceTypeSeeder extends Seeder
{
    /**
     * Seed resource types for each category.
     */
    public function run(): void
    {
        $categories = ResourceCategory::all();

        foreach ($categories as $category) {
            ResourceType::firstOrCreate(
                [
                    'resource_category_id' => $category->id,
                    'name' => $category->name . ' Type',
                ],
                [
                    'description' => 'Resource type for ' . $category->name,
                ]
            );
        }
    }
}
