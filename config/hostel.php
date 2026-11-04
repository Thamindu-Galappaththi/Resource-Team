<?php

return [
    'category_name' => 'Hostel Room',
    'locations' => ['Welisara', 'Moratuwa'],
    'reference_prefix' => 'HST',

    // The Hostel Room category has exactly one resource type.
    'bed_type_name' => 'Bed',

    // Starting beds for a new hostel record; each location's record can be edited later as beds are added.
    'bed_groups' => [
        'permanent' => ['label' => 'Permanent resident (4 years)', 'short' => 'Permanent', 'default' => 10],
        'female' => ['label' => 'Ladies', 'short' => 'Ladies', 'default' => 40],
        'male' => ['label' => 'Male', 'short' => 'Male', 'default' => 40],
    ],

    // Upper limit per bed group, only to catch typing mistakes.
    'max_beds_per_group' => 1000,
];
