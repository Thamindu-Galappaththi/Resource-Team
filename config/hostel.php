<?php

return [
    'category_name' => 'Hostel Room',
    'reference_prefix' => 'HST',

    // The Hostel Room category has exactly one resource type.
    'bed_type_name' => 'Bed',

    // Fixed bed capacity per hostel location (each location gets the full set).
    'bed_groups' => [
        'permanent' => ['label' => 'Permanent resident (4 years)', 'short' => 'Permanent', 'capacity' => 10],
        'female' => ['label' => 'Ladies', 'short' => 'Ladies', 'capacity' => 40],
        'male' => ['label' => 'Male', 'short' => 'Male', 'capacity' => 40],
    ],
];
