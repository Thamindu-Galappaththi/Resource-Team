<?php

namespace Database\Seeders;

use App\Helpers\RoleHelper;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Demo accounts for local development. Password is Password@123 except Developer.
     * Username is NIC (BRD 7.2.1 / 7.2.3). Email also works.
     */
    public function run(): void
    {
        $accounts = [
            ['slug' => 'developer', 'name' => 'Developer', 'nic' => '000000000000', 'email' => 'developer@nebula.local', 'password' => 'dev12345'],
            ['slug' => 'super_admin', 'name' => 'Super Admin', 'nic' => '199001010001', 'email' => 'superadmin@nebula.local'],
            ['slug' => 'admin', 'name' => 'Admin User', 'nic' => '199001010002', 'email' => 'admin@nebula.local'],
            ['slug' => 'coordinator', 'name' => 'Coordinator User', 'nic' => '199001010003', 'email' => 'coordinator@nebula.local'],
            ['slug' => 'resource_owner', 'name' => 'Resource Owner', 'nic' => '199001010004', 'email' => 'owner@nebula.local'],
            ['slug' => 'slt_employee', 'name' => 'SLT Employee', 'nic' => '199001010005', 'email' => 'slt@nebula.local'],
            ['slug' => 'nebula_sms_user', 'name' => 'Nebula SMS User', 'nic' => '199001010006', 'email' => 'sms@nebula.local'],
            ['slug' => 'management', 'name' => 'Management User', 'nic' => '199001010007', 'email' => 'management@nebula.local'],
            ['slug' => 'canteen', 'name' => 'Canteen User', 'nic' => '199001010008', 'email' => 'canteen@nebula.local'],
            ['slug' => 'hostel_manager', 'name' => 'Hostel Manager', 'nic' => '199001010009', 'email' => 'hostel@nebula.local'],
        ];

        foreach ($accounts as $account) {
            $role = Role::query()->where('slug', $account['slug'])->first();

            if (! $role) {
                continue;
            }

            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'nic' => $account['nic'],
                    'phone' => '0770000000',
                    'location' => 'Welisara',
                    'password' => $account['password'] ?? 'Password@123',
                    'role_id' => $role->id,
                    'user_role' => $role->slug,
                    'is_active' => true,
                    'slt_employee' => $account['slug'] === 'slt_employee',
                ]
            );
        }

        User::query()->whereNull('role_id')->each(function (User $user) {
            $slug = RoleHelper::normalizeRole($user->user_role);
            $role = Role::query()->where('slug', $slug)->first();

            if ($role) {
                $user->forceFill([
                    'role_id' => $role->id,
                    'user_role' => $role->slug,
                    'is_active' => $user->is_active ?? true,
                ])->save();
            }
        });
    }
}
