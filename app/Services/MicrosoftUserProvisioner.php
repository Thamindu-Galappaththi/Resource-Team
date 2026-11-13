<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Rules\SriLankanNic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MicrosoftUserProvisioner
{
    public function __construct(private readonly SltEmployeeDirectory $directory)
    {
    }

    /**
     * @param  array{id: string, email: string, name: string, service_id?: ?string, designation?: ?string, location?: ?string, phone?: ?string, nic?: ?string}  $profile
     */
    public function sync(array $profile): User
    {
        $this->assertAllowedDomain($profile['email']);
        $profile = $this->enrichFromDirectory($profile);

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
        $byMicrosoftId = $profile['id'] !== ''
            ? User::withTrashed()->where('microsoft_id', $profile['id'])->first()
            : null;

        $byEmail = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [strtolower($profile['email'])])
            ->first();

        if ($byMicrosoftId && $byEmail && $byMicrosoftId->isNot($byEmail)) {
            $byMicrosoftId->forceFill(['microsoft_id' => null])->save();

            return $byEmail;
        }

        return $byEmail ?? $byMicrosoftId;
    }

    /**
     * @param  array{id: string, email: string, name: string, service_id?: ?string, designation?: ?string, location?: ?string, phone?: ?string, nic?: ?string}  $profile
     */
    private function updateExisting(User $user, array $profile): User
    {
        if ($user->trashed()) {
            $user->restore();
        }

        if (! $user->is_active) {
            throw new RuntimeException('Your account is inactive. Please contact an administrator.');
        }

        $emailTaken = User::withTrashed()
            ->whereKeyNot($user->id)
            ->whereRaw('LOWER(email) = ?', [strtolower($profile['email'])])
            ->exists();

        $user->fill([
            'name' => $profile['name'],
            'email' => $emailTaken ? $user->email : $profile['email'],
            'microsoft_id' => $profile['id'] !== '' ? $profile['id'] : $user->microsoft_id,
            'slt_employee' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'service_id' => $user->service_id ?: ($profile['service_id'] ?? null),
            'nic' => $user->nic ?: $this->availableNic($profile['nic'] ?? null, $user->id),
            'designation' => $user->designation ?: ($profile['designation'] ?? null),
            'location' => $user->location ?: ($profile['location'] ?? null),
            'phone' => $user->phone ?: ($profile['phone'] ?? null),
        ]);

        $this->assignSltEmployeeRole($user);
        $user->save();

        return $user->fresh() ?? $user;
    }

    /**
     * @param  array{id: string, email: string, name: string, service_id?: ?string, designation?: ?string, location?: ?string, phone?: ?string, nic?: ?string}  $profile
     */
    private function createEmployee(array $profile): User
    {
        $role = $this->sltEmployeeRole();

        $user = User::create([
            'name' => $profile['name'],
            'email' => $profile['email'],
            'microsoft_id' => $profile['id'] !== '' ? $profile['id'] : null,
            'service_id' => $profile['service_id'] ?? null,
            'nic' => $this->availableNic($profile['nic'] ?? null),
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

        if (! $this->hasAssignedStaffRole($user)) {
            $user->role_id = $role->id;
            $user->user_role = $role->slug;
        }

        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    private function hasAssignedStaffRole(User $user): bool
    {
        $slug = $user->roleSlug();

        return $slug !== '' && $slug !== 'slt_employee';
    }

    private function sltEmployeeRole(): Role
    {
        $role = Role::query()->where('slug', 'slt_employee')->first();

        if (! $role) {
            throw new RuntimeException('Microsoft sign-in is not available yet. Please contact an administrator.');
        }

        return $role;
    }

    /**
     * @param  array{id: string, email: string, name: string, service_id?: ?string, designation?: ?string, location?: ?string, phone?: ?string, nic?: ?string}  $profile
     * @return array{id: string, email: string, name: string, service_id?: ?string, designation?: ?string, location?: ?string, phone?: ?string, nic?: ?string}
     */
    private function enrichFromDirectory(array $profile): array
    {
        $employeeNo = trim((string) ($profile['service_id'] ?? ''));
        if ($employeeNo === '') {
            return $profile;
        }

        try {
            $record = $this->directory->lookup($employeeNo);
        } catch (Throwable) {
            return $profile;
        }

        $directoryEmail = strtolower(trim((string) ($record['email'] ?? '')));
        $loginEmail = strtolower($profile['email']);
        if ($directoryEmail === '' || $directoryEmail !== $loginEmail) {
            return $profile;
        }

        if (filled($record['service_id'] ?? null)) {
            $profile['service_id'] = (string) $record['service_id'];
        }

        if (blank($profile['phone'] ?? null) && filled($record['phone'] ?? null)) {
            $profile['phone'] = Str::limit((string) $record['phone'], 20, '');
        }

        if (blank($profile['nic'] ?? null) && filled($record['nic'] ?? null)) {
            $profile['nic'] = $record['nic'];
        }

        if (blank($profile['designation'] ?? null) && filled($record['designation'] ?? null)) {
            $profile['designation'] = (string) $record['designation'];
        }

        return $profile;
    }

    private function availableNic(?string $nic, ?int $ignoreUserId = null): ?string
    {
        $nic = SriLankanNic::normalize($nic);
        if ($nic === '' || preg_match(SriLankanNic::pattern(), $nic) !== 1) {
            return null;
        }

        $taken = User::withTrashed()->where('nic', $nic);
        if ($ignoreUserId) {
            $taken->whereKeyNot($ignoreUserId);
        }

        return $taken->exists() ? null : $nic;
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
