<?php

return [
    // Developer mode: full platform access.
    'developer' => ['*'],

    // The remaining roles are intentionally empty for now
    // and can be configured incrementally per permission key.
    'super admin' => [
        'dashboard',
        // 'user.create',
        // 'user.management',
        // 'reservations.calendar',
        // 'reservations.create',
        // 'reservations.index',
        // 'resources.create',
        // 'resources.index',
        // 'approvals.special',
    ],
    'admin' => ['dashboard'],
    'cordinator' => ['dashboard'],
    'resource owner' => ['dashboard'],
    'nebula users' => ['dashboard'],
];
