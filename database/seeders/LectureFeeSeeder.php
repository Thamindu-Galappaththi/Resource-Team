<?php

namespace Database\Seeders;

use App\Models\RateCard;
use App\Models\RateRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LectureFeeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $rateCard = RateCard::updateOrCreate(
                ['name' => 'CHRO Lecture Fee Payment Scheme'],
                [
                    'currency' => 'LKR',
                    'effective_from' => '2019-08-09',
                    'effective_to' => null,
                    'is_active' => true,
                ]
            );

            foreach (config('lecture-fees.rates') as $type => $levels) {
                foreach ($levels as $courseLevel => $bands) {
                    foreach ($bands as $qualification => $hourly) {
                        RateRule::updateOrCreate(
                            [
                                'rate_card_id' => $rateCard->id,
                                'user_type' => $type,
                                'department' => $courseLevel,
                                'qualification' => $qualification,
                            ],
                            [
                                'location' => null,
                                'hourly_rate_minor' => (int) round($hourly * 100),
                                'is_active' => true,
                                'conditions' => [
                                    'scheme' => 'TDC-LF 01',
                                    'unit' => 'hour',
                                    'source' => 'CHRO Group Lecture Fee Payment Scheme',
                                ],
                            ]
                        );
                    }
                }
            }
        });
    }
}
