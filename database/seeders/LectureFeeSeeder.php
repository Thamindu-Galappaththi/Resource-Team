<?php

namespace Database\Seeders;

use App\Models\LectureSession;
use App\Models\LecturerPayable;
use App\Models\LecturerProfile;
use App\Models\RateCard;
use App\Models\RateRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LectureFeeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Find the lecturer profiles created by LecturerResourceSeeder.
            $lecturers = LecturerProfile::whereIn('service_id', [
                'LECT-001',
                'LECT-002',
                'LECT-003',
            ])->get()->keyBy('service_id');

            if ($lecturers->count() !== 3) {
                throw new \RuntimeException(
                    'Run LecturerResourceSeeder before LectureFeeSeeder.'
                );
            }

            // Create or update the demonstration rate card.
            $rateCard = RateCard::updateOrCreate(
                ['name' => 'Demo Lecturer Rates 2026'],
                [
                    'currency' => 'LKR',
                    'effective_from' => '2026-01-01',
                    'effective_to' => null,
                    'is_active' => true,
                ]
            );

            // Illustrative hourly rates, not approved payment rates.
            $examples = [
                [
                    'service_id' => 'LECT-001',
                    'date' => '2026-10-05',
                    'hours' => 2.00,
                    'qualification' => 'Degree',
                    'department' => 'Software Engineering',
                    'location' => 'Welisara',
                    'rate' => 150000,
                ],
                [
                    'service_id' => 'LECT-002',
                    'date' => '2026-10-08',
                    'hours' => 3.00,
                    'qualification' => 'Masters',
                    'department' => 'Software Engineering',
                    'location' => 'Moratuwa',
                    'rate' => 200000,
                ],
                [
                    'service_id' => 'LECT-003',
                    'date' => '2026-10-12',
                    'hours' => 1.50,
                    'qualification' => 'PhD',
                    'department' => 'Software Engineering',
                    'location' => 'Peradeniya',
                    'rate' => 250000,
                ],
            ];

            foreach ($examples as $example) {
                $lecturer = $lecturers->get($example['service_id']);

                $rule = RateRule::updateOrCreate(
                    [
                        'rate_card_id' => $rateCard->id,
                        'qualification' => $example['qualification'],
                        'department' => $example['department'],
                    ],
                    [
                        'location' => null,
                        'user_type' => null,
                        'hourly_rate_minor' => $example['rate'],
                        'conditions' => null,
                    ]
                );

                // Reuse a session for the same lecturer and date.
                $session = LectureSession::firstOrCreate(
                    [
                        'lecturer_id' => $lecturer->id,
                        'session_date' => $example['date'],
                    ],
                    [
                        'hours' => $example['hours'],
                        'qualification' => $example['qualification'],
                        'department' => $example['department'],
                        'location' => $example['location'],
                        'status' => 'completed',
                    ]
                );

                LecturerPayable::updateOrCreate(
                    ['lecture_session_id' => $session->id],
                    [
                        'lecturer_id' => $lecturer->id,
                        'rate_card_id' => $rateCard->id,
                        'hours' => $session->hours,
                        'hourly_rate_minor' => $example['rate'],
                        'net_amount_minor' => (int) round(
                            (float) $session->hours * $example['rate']
                        ),
                        'currency' => 'LKR',
                        'rate_snapshot' => [
                            'rate_card_name' => $rateCard->name,
                            'rate_rule_id' => $rule->id,
                            'qualification' => $example['qualification'],
                            'department' => $example['department'],
                            'hourly_rate_minor' => $example['rate'],
                        ],
                        'status' => 'pending',
                    ]
                );
            }
        });
    }
}
