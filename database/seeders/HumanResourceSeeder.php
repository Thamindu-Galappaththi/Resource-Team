<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HumanResourceSeeder extends Seeder
{
    public function run(): void
    {
        // Create or reuse the Human Resource category
        $category = DB::table('resource_categories')
            ->where('name', 'Human Resource')
            ->first();

        if (!$category) {
            $categoryId = DB::table('resource_categories')->insertGetId([
                'name' => 'Human Resource',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $categoryId = $category->id;
        }

        // Create or reuse the Lecturer resource type
        DB::table('resource_types')->updateOrInsert(
            [
                'resource_category_id' => $categoryId,
                'name' => 'Lecturer',
            ],
            [
                'description' => 'Lecturers registered as Human Resources',
                'updated_at' => now(),
            ]
        );
    }
}