<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionIds = [];

        foreach (config('rbac.permissions', []) as $slug => $meta) {
            $permission = Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $meta['name'],
                    'module' => $meta['module'] ?? null,
                ]
            );
            $permissionIds[$slug] = $permission->id;
        }

        $sort = 1;

        foreach (config('rbac.roles', []) as $slug => $meta) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $meta['name'],
                    'description' => $meta['description'] ?? null,
                    'sort_order' => $sort++,
                    'is_active' => true,
                ]
            );

            $assigned = config('rbac.role_permissions.'.$slug, []);

            if (in_array('*', $assigned, true)) {
                $role->permissions()->sync(array_values($permissionIds));
                continue;
            }

            $ids = [];
            foreach ($assigned as $permissionSlug) {
                if (isset($permissionIds[$permissionSlug])) {
                    $ids[] = $permissionIds[$permissionSlug];
                }
            }

            $role->permissions()->sync($ids);
        }
    }
}
