<?php

namespace App\Helpers;

class RoleHelper
{
    /**
     * Map legacy labels onto the BRD role slugs stored in the roles table.
     */
    private const ROLE_ALIASES = [
        'developer' => 'developer',
        'developer@nebula.local' => 'developer',
        'program administrator (level 01)' => 'super_admin',
        'program administrator (level 02)' => 'admin',
        'pa-l1' => 'super_admin',
        'pa-l2' => 'admin',
        'super admin' => 'super_admin',
        'super_admin' => 'super_admin',
        'admin' => 'admin',
        'cordinator' => 'coordinator',
        'coordinator' => 'coordinator',
        'resource owner' => 'resource_owner',
        'resource_owner' => 'resource_owner',
        'slt employee' => 'slt_employee',
        'slt_employee' => 'slt_employee',
        'nebula users' => 'nebula_sms_user',
        'nebula user' => 'nebula_sms_user',
        'nebula_users' => 'nebula_sms_user',
        'nebula_user' => 'nebula_sms_user',
        'nebula_sms_user' => 'nebula_sms_user',
        'management' => 'management',
        'management role' => 'management',
        'management_role' => 'management',
        'canteen' => 'canteen',
        'hostel manager' => 'hostel_manager',
        'hostel_manager' => 'hostel_manager',
    ];

    public static function hasPermission(?string $role, ?string $permission): bool
    {
        $role = self::normalizeRole($role);
        $permission = trim((string) $permission);

        if ($role === '' || $permission === '') {
            return false;
        }

        if (in_array($role, ['developer', 'super_admin'], true)) {
            return true;
        }

        $allowed = config('rbac.role_permissions.'.$role, []);

        if (! is_array($allowed) || $allowed === []) {
            return false;
        }

        if (in_array('*', $allowed, true) || in_array($permission, $allowed, true)) {
            return true;
        }

        foreach ($allowed as $entry) {
            if (! is_string($entry) || ! str_ends_with($entry, '*')) {
                continue;
            }

            $prefix = substr($entry, 0, -1);
            if ($prefix !== '' && str_starts_with($permission, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public static function normalizeRole(?string $role): string
    {
        $normalized = strtolower(trim((string) $role));

        if ($normalized === '') {
            return '';
        }

        return self::ROLE_ALIASES[$normalized] ?? str_replace(' ', '_', $normalized);
    }

    public static function isDeveloper(?string $role): bool
    {
        return self::normalizeRole($role) === 'developer';
    }

    public static function hasAnyRole(?string $role, array $roles): bool
    {
        $normalizedRole = self::normalizeRole($role);

        if ($normalizedRole === '') {
            return false;
        }

        foreach ($roles as $allowedRole) {
            if ($normalizedRole === self::normalizeRole((string) $allowedRole)) {
                return true;
            }
        }

        return false;
    }
}
