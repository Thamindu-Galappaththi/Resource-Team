<?php

return [
    'types' => [
        'internal_visiting' => 'Internal visiting lectures',
        'external_visiting' => 'External visiting lectures',
        'internal_trainer' => 'Internal trainers',
    ],
    'course_levels' => [
        'degree' => 'Degree',
        'diploma' => 'Diploma',
        'certificate' => 'Certificate',
        'internal_training' => 'Internal training',
    ],
    'qualifications' => [
        'non_executive' => 'Non-executives / Non-degree holder',
        'degree' => 'Degree or equivalent in relevant field',
        'masters' => 'Masters / CEng in relevant field',
        'phd' => 'PHD / DBA or Fellow Member of IESL',
    ],
    'rates' => [
        'internal_visiting' => [
            'degree' => ['non_executive' => 500, 'degree' => 1000, 'masters' => 2000, 'phd' => 3000],
            'diploma' => ['non_executive' => 400, 'degree' => 750, 'masters' => 1500, 'phd' => 2000],
            'certificate' => ['non_executive' => 300, 'degree' => 500, 'masters' => 1000, 'phd' => 1500],
            'internal_training' => ['non_executive' => 450, 'degree' => 800, 'masters' => 1600, 'phd' => 2400],
        ],
        'external_visiting' => [
            'degree' => ['non_executive' => 1000, 'degree' => 2000, 'masters' => 3000, 'phd' => 4000],
            'diploma' => ['non_executive' => 750, 'degree' => 1500, 'masters' => 2500, 'phd' => 3500],
            'certificate' => ['non_executive' => 500, 'degree' => 1000, 'masters' => 2000, 'phd' => 3000],
            'internal_training' => ['non_executive' => 1000, 'degree' => 2000, 'masters' => 3000, 'phd' => 4000],
        ],
        'internal_trainer' => [
            'degree' => ['degree' => 1000, 'masters' => 1500, 'phd' => 2000],
        ],
    ],
];
