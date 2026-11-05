<?php

namespace Database\Seeders;

use App\Services\AnnexureRateCardImporter;
use Illuminate\Database\Seeder;

class ResourceRateSeeder extends Seeder
{
    public function run(): void
    {
        app(AnnexureRateCardImporter::class)->importPath(database_path('data/rate_card_annexure1.csv'));
    }
}
