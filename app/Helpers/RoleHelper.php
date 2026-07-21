<?php

namespace App\Helpers;

class RoleHelper
{
    private const ROLE_ALIASES = [
        'developer' => 'developer',
        'developer@nebula.local' => 'developer',
        'program administrator (level 01)' => 'super admin',
        'program administrator (level 02)' => 'admin',
        'super admin' => 'super admin',
        'admin' => 'admin',
        'cordinator' => 'cordinator',
        'coordinator' => 'cordinator',
        'resource owner' => 'resource owner',
        'nebula users' => 'nebula users',
    ];

    /**
     * Check whether a role can access a specific permission key.
     */
    public static function hasPermission(?string $role, ?string $permission): bool
    {
        $role = self::normalizeRole($role);
        $permission = trim((string) $permission);

        if ($role === '' || $permission === '') {
            return false;
        }

        if (self::isDeveloper($role)) {
            return true;
        }

        $permissionsConfig = config('role_permissions');

        // Safe fallback: when no mapping is configured, deny permission checks.
        // This avoids rendering menu links for routes that may not exist yet.
        if (!is_array($permissionsConfig) || $permissionsConfig === []) {
            return false;
        }

        $allowed = $permissionsConfig[$role] ?? [];

        if (!is_array($allowed) || $allowed === []) {
            return false;
        }

        if (in_array('*', $allowed, true) || in_array($permission, $allowed, true)) {
            return true;
        }

        foreach ($allowed as $entry) {
            if (!is_string($entry) || !str_ends_with($entry, '*')) {
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

        return self::ROLE_ALIASES[$normalized] ?? $normalized;
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
