<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MicrosoftUserProvisioner
{
    /**
     * @param  array{id: string, email: string, name: string, service_id?: ?string, designation?: ?string, location?: ?string, phone?: ?string}  $profile
     */
    public function sync(array $profile): User
    {
        $this->assertAllowedDomain($profile['email']);

        return DB::transaction(function () use ($profile) {
            $user = $this->findExisting($profile);

            if ($user) {
                return $this->updateExisting($user, $profile);
            }

            return $this->createEmployee($profile);
        });
    }

    /**
     * @param  array{id: string, email: string}  $profile
     */
    private function findExisting(array $profile): ?User
    {
        if ($profile['id'] !== '') {
            $byMicrosoftId = User::withTrashed()->where('microsoft_id', $profile['id'])->first();

            if ($byMicrosoftId) {
                return $byMicrosoftId;
            }
        }

        return User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$profile['email']])
            ->first();
    }

    /**
     * @param  array{id: string, email: string, name: string, service_id?: ?string, designation?: ?string, location?: ?string, phone?: ?string}  $profile
     */
    private function updateExisting(User $user, array $profile): User
    {
        if ($user->trashed()) {
            $user->restore();
        }

        if (! $user->is_active) {
            throw new RuntimeException('Your account is inactive. Please contact an administrator.');
        }

        if ($this->isPrivilegedStaff($user) && ! $user->isSltEmployeeStaff()) {
            throw new RuntimeException('Microsoft sign-in is only available for SLT employee staff.');
        }

        $user->fill([
            'name' => $profile['name'],
            'email' => $profile['email'],
            'microsoft_id' => $profile['id'] !== '' ? $profile['id'] : $user->microsoft_id,
            'slt_employee' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'service_id' => $user->service_id ?: ($profile['service_id'] ?? null),
            'designation' => $user->designation ?: ($profile['designation'] ?? null),
            'location' => $user->location ?: ($profile['location'] ?? null),
            'phone' => $user->phone ?: ($profile['phone'] ?? null),
        ]);

        $this->assignSltEmployeeRole($user);
        $user->save();

        return $user->fresh() ?? $user;
    }

    /**
     * @param  array{id: string, email: string, name: string, service_id?: ?string, designation?: ?string, location?: ?string, phone?: ?string}  $profile
     */
    private function createEmployee(array $profile): User
    {
        $role = $this->sltEmployeeRole();

        $user = User::create([
            'name' => $profile['name'],
            'email' => $profile['email'],
            'microsoft_id' => $profile['id'] !== '' ? $profile['id'] : null,
            'service_id' => $profile['service_id'] ?? null,
            'designation' => $profile['designation'] ?? null,
            'location' => $profile['location'] ?? null,
            'phone' => $profile['phone'] ?? null,
            'slt_employee' => true,
            'password' => Str::password(40),
            'role_id' => $role->id,
            'user_role' => $role->slug,
            'is_active' => true,
            'email_verified_at' => now(),
            'password_setup_at' => now(),
        ]);

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    private function assignSltEmployeeRole(User $user): void
    {
        $role = $this->sltEmployeeRole();

        if (! $this->isPrivilegedStaff($user)) {
            $user->role_id = $role->id;
            $user->user_role = $role->slug;
        }

        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    private function sltEmployeeRole(): Role
    {
        $role = Role::query()->where('slug', 'slt_employee')->first();

        if (! $role) {
            throw new RuntimeException('Microsoft sign-in is not available yet. Please contact an administrator.');
        }

        return $role;
    }

    private function isPrivilegedStaff(User $user): bool
    {
        return $user->hasRole('developer', 'super_admin', 'admin');
    }

    private function assertAllowedDomain(string $email): void
    {
        $domains = config('services.microsoft.allowed_domains', []);

        if (! is_array($domains) || $domains === []) {
            return;
        }

        $host = strtolower(Str::after($email, '@'));

        if (! in_array($host, $domains, true)) {
            throw new RuntimeException('Microsoft sign-in is only available for SLT employee staff.');
        }
    }
}
