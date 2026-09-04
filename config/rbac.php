<?php

/**
 * Source of truth for roles, permissions, and role-based dashboard placeholders.
 * RolePermissionSeeder reads this file. BRD sections 5, 7.2.1–7.2.4, 8.1–8.2, 11.2.
 */
return [

    'roles' => [
        'developer' => [
            'name' => 'Developer',
            'description' => 'Develops, maintains, and supports the platform.',
        ],
        'super_admin' => [
            'name' => 'Super Admin',
            'description' => 'Full system control: users, roles, reservations, resources, approvals, and payments.',
        ],
        'admin' => [
            'name' => 'Admin',
            'description' => 'Daily operations, reservation approvals, and resource management.',
        ],
        'coordinator' => [
            'name' => 'Coordinator',
            'description' => 'Creates and manages reservations and schedules in real time.',
        ],
        'resource_owner' => [
            'name' => 'Resource Owner',
            'description' => 'Approves reservations for owned resources and monitors usage.',
        ],
        'slt_employee' => [
            'name' => 'SLT Employee',
            'description' => 'Can reserve resources. Details appear on related reservations.',
        ],
        'nebula_sms_user' => [
            'name' => 'Nebula SMS User',
            'description' => 'View-only access to personal schedules and reservations.',
        ],
        'management' => [
            'name' => 'Management',
            'description' => 'Oversight and decision-making reports for the assigned role.',
        ],
        'canteen' => [
            'name' => 'Canteen',
            'description' => 'Views meal forecasts and manages large group canteen orders.',
        ],
        'hostel_manager' => [
            'name' => 'Hostel Manager',
            'description' => 'Manages hostel reservation requests and room availability.',
        ],
    ],

    'permissions' => [
        'dashboard' => ['name' => 'View dashboard', 'module' => 'dashboard'],
        'user.create' => ['name' => 'Create user', 'module' => 'users'],
        'user.management' => ['name' => 'Manage users', 'module' => 'users'],
        'reservations.calendar' => ['name' => 'Reservation calendar', 'module' => 'reservations'],
        'reservations.create' => ['name' => 'Create reservation', 'module' => 'reservations'],
        'reservations.index' => ['name' => 'Existing reservations', 'module' => 'reservations'],
        'resources.create' => ['name' => 'Create resource', 'module' => 'resources'],
        'resources.index' => ['name' => 'Existing resources', 'module' => 'resources'],
        'resources.calendar' => ['name' => 'Resource calendar', 'module' => 'resources'],
        'approvals.special' => ['name' => 'Special approvals', 'module' => 'approvals'],
        'payments.view' => ['name' => 'View payments', 'module' => 'payments'],
        'canteen.view' => ['name' => 'Canteen dashboard', 'module' => 'canteen'],
        'hostel.view' => ['name' => 'Hostel reservations', 'module' => 'hostel'],
    ],

    /*
    | Developer receives every permission. Other roles follow the BRD module actors.
    */
    'role_permissions' => [
        'developer' => ['*'],
        'super_admin' => ['*'],
        'admin' => [
            'dashboard',
            'user.create',
            'user.management',
            'reservations.calendar',
            'reservations.create',
            'reservations.index',
            'resources.create',
            'resources.index',
            'resources.calendar',
            'approvals.special',
            'payments.view',
        ],
        'coordinator' => [
            'dashboard',
            'user.create',
            'reservations.calendar',
            'reservations.create',
            'reservations.index',
        ],
        'resource_owner' => [
            'dashboard',
            'reservations.calendar',
            'reservations.create',
            'reservations.index',
            'resources.create',
            'resources.index',
            'resources.calendar',
        ],
        'slt_employee' => [
            'dashboard',
            'reservations.calendar',
            'reservations.create',
            'reservations.index',
        ],
        'nebula_sms_user' => [
            'dashboard',
            'reservations.calendar',
            'reservations.index',
        ],
        'management' => [
            'dashboard',
            'reservations.calendar',
            'resources.index',
            'payments.view',
        ],
        'canteen' => [
            'dashboard',
            'canteen.view',
        ],
        'hostel_manager' => [
            'dashboard',
            'hostel.view',
            'reservations.calendar',
        ],
    ],

    /*
    | Placeholder widgets only — dashboards are not fully built yet.
    | DashboardController + permission middleware use this metadata.
    */
    'dashboards' => [
        'developer' => [
            'title' => 'Developer Dashboard',
            'subtitle' => 'Technical monitoring for system health, logs, and deployments.',
            'widgets' => ['System health', 'Application logs', 'API status', 'Backup monitoring', 'Error reports'],
        ],
        'super_admin' => [
            'title' => 'Super Admin Dashboard',
            'subtitle' => 'Centralized view of reservations, users, payments, and analytics.',
            'widgets' => ['Total reservations', 'Active users', 'Payment summaries', 'System notifications', 'Location analytics'],
        ],
        'admin' => [
            'title' => 'Admin Dashboard',
            'subtitle' => 'Daily operations: reservations, resources, approvals, and activity.',
            'widgets' => ['Daily reservations', 'Available resources', 'Pending approvals', 'Maintenance alerts', 'Recent activity'],
        ],
        'coordinator' => [
            'title' => 'Coordinator Dashboard',
            'subtitle' => 'Reservation requests, status, upcoming bookings, and schedules.',
            'widgets' => ['Reservation requests', 'Reservation status', 'Upcoming bookings', 'Resource schedules'],
        ],
        'resource_owner' => [
            'title' => 'Resource Owner Dashboard',
            'subtitle' => 'Assigned resources, pending approvals, and usage monitoring.',
            'widgets' => ['Assigned resources', 'Pending approvals', 'Approved requests', 'Rejected requests', 'Maintenance schedules'],
        ],
        'slt_employee' => [
            'title' => 'SLT Employee Dashboard',
            'subtitle' => 'Available resources, new reservations, and personal booking status.',
            'widgets' => ['Available resources', 'My reservations', 'Reservation status'],
        ],
        'nebula_sms_user' => [
            'title' => 'Nebula SMS User Dashboard',
            'subtitle' => 'View-only access to personal reservation history and schedules.',
            'widgets' => ['Reservation history', 'Reservation status', 'Notifications', 'Upcoming schedules'],
        ],
        'management' => [
            'title' => 'Management Dashboard',
            'subtitle' => 'Oversight reports and resource utilization for decision making.',
            'widgets' => ['Resource utilization', 'Reservation summaries', 'Operational reports'],
        ],
        'canteen' => [
            'title' => 'Canteen Dashboard',
            'subtitle' => 'Daily customer forecasts, meal orders, and group reservations.',
            'widgets' => ['Expected customers', 'Confirmed meals', 'Order volumes', 'Group orders'],
        ],
        'hostel_manager' => [
            'title' => 'Hostel Manager Dashboard',
            'subtitle' => 'Hostel bookings, room availability, and pending approvals.',
            'widgets' => ['Room availability', 'Pending hostel bookings', 'Check-ins today'],
        ],
    ],
];
