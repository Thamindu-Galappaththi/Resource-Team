<?php

namespace App\Models;

use App\Helpers\RoleHelper;
use App\Notifications\SetupPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /** @var Collection<int, string>|null */
    private ?Collection $normalizedRoleSlugs = null;

    protected $fillable = [
        'name',
        'service_id',
        'slt_employee',
        'nic',
        'email',
        'microsoft_id',
        'phone',
        'location',
        'password',
        'user_role',
        'role_id',
        'designation',
        'user_type',
        'user_profile',
        'is_active',
        'password_setup_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'slt_employee' => 'boolean',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'password_setup_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->role_id && $user->relationLoaded('role') === false) {
                $user->load('role');
            }

            if ($user->role) {
                $user->user_role = $user->role->slug;
            }
        });
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * All roles assigned to this user. The singular role relation remains the
     * primary role used by legacy data and dashboard selection.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Pivot roles plus the primary role, without duplicates or mutating relations.
     *
     * @return Collection<int, Role>
     */
    public function assignedRoles(): Collection
    {
        $this->loadMissing('role', 'roles');

        return $this->roles
            ->concat(collect([$this->role]))
            ->filter()
            ->unique('id')
            ->values();
    }

    public function extraPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->latest();
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new SetupPasswordNotification($token));
    }

    public function hasSetPassword(): bool
    {
        return $this->password_setup_at !== null;
    }

    public function campusLocationLabel(): string
    {
        $location = trim((string) $this->location);

        if ($location === '') {
            return '';
        }

        return trim(Str::afterLast($location, ' - ')) ?: $location;
    }

    public function markPasswordSetupCompleted(): void
    {
        $this->forceFill(['password_setup_at' => now()])->save();
    }

    /**
     * @return list<string>
     */
    public function grantedPermissionSlugs(): array
    {
        $all = array_keys(config('rbac.permissions', []));
        $slugs = collect();

        $this->loadMissing('role', 'roles.permissions');

        foreach ($this->assignedRoles() as $role) {
            if (in_array($role->slug, ['developer', 'super_admin'], true)) {
                return $all;
            }

            $slugs = $slugs->merge($role->permissions->pluck('slug'));
        }

        if ($this->extraPermissionTableExists()) {
            $this->loadMissing('extraPermissions');
            $slugs = $slugs->merge($this->extraPermissions->pluck('slug'));
        }

        $fromConfig = collect([$this->roleSlug()])
            ->filter()
            ->flatMap(fn (string $slug) => in_array($slug, ['developer', 'super_admin'], true)
                ? $all
                : config('rbac.role_permissions.'.$slug, []));

        return $slugs->merge($fromConfig)->unique()->values()->all();
    }

    public function roleSlug(): string
    {
        return $this->role?->slug ?? RoleHelper::normalizeRole($this->user_role);
    }

    public function isSltEmployeeStaff(): bool
    {
        return $this->slt_employee || $this->hasRole('slt_employee');
    }

    public function usesSltEmployeeWorkspace(): bool
    {
        if (session('auth_portal') === 'microsoft') {
            return true;
        }

        return $this->roleSlug() === 'slt_employee';
    }

    /**
     * Roles that apply to the current login. Microsoft sessions use SLT Employee
     * only. Staff-portal sessions ignore SLT Employee when any other role exists.
     *
     * @return Collection<int, string>
     */
    public function workspaceRoleSlugs(): Collection
    {
        if ($this->usesSltEmployeeWorkspace()) {
            return collect(['slt_employee']);
        }

        $staffRoles = $this->normalizedRoleSlugs()
            ->reject(fn (string $slug) => $slug === 'slt_employee')
            ->values();

        return $staffRoles->isNotEmpty() ? $staffRoles : $this->normalizedRoleSlugs();
    }

    public function hasRole(string ...$roles): bool
    {
        $assignedRoles = $this->normalizedRoleSlugs();

        foreach ($roles as $role) {
            if ($assignedRoles->contains(RoleHelper::normalizeRole($role))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, string>
     */
    private function normalizedRoleSlugs(): Collection
    {
        if ($this->normalizedRoleSlugs instanceof Collection) {
            return $this->normalizedRoleSlugs;
        }

        $this->loadMissing('role', 'roles');

        return $this->normalizedRoleSlugs = $this->roles
            ->pluck('slug')
            ->push($this->roleSlug())
            ->filter()
            ->map(fn (string $slug) => RoleHelper::normalizeRole((string) $slug))
            ->unique()
            ->values();
    }

    /**
     * Admins see every special approval; hostel managers see hostel stays only;
     * any other special approver sees every booking except hostel stays.
     *
     * @return 'all'|'hostel'|'standard'
     */
    public function specialApprovalScope(): string
    {
        return match (true) {
            $this->hasRole('developer', 'super_admin', 'admin') => 'all',
            $this->hasRole('hostel_manager') => 'hostel',
            default => 'standard',
        };
    }

    public function canSpecialApprove(Reservation $reservation): bool
    {
        if (! $this->hasPermission('approvals.special')) {
            return false;
        }

        $hostel = $reservation->type === \App\Enums\ReservationType::HOSTEL->value;

        return match ($this->specialApprovalScope()) {
            'all' => true,
            'hostel' => $hostel,
            default => ! $hostel,
        };
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $active = $this->workspaceRoleSlugs();

        if ($active->contains(fn (string $slug) => in_array($slug, ['developer', 'super_admin'], true))) {
            return true;
        }

        $this->loadMissing('role.permissions', 'roles.permissions');

        $roles = $this->assignedRoles()->filter(
            fn (Role $role) => $active->contains(RoleHelper::normalizeRole((string) $role->slug))
        );

        if ($roles->contains(fn (Role $role) => $role->hasPermission($permission))) {
            return true;
        }

        if ($active->contains(fn (string $slug) => RoleHelper::hasPermission($slug, $permission))) {
            return true;
        }

        if ($this->usesSltEmployeeWorkspace()) {
            return false;
        }

        return $this->extraPermissionTableExists()
            && $this->loadMissing('extraPermissions')->extraPermissions->contains('slug', $permission);
    }

    public function avatarUrl(): string
    {
        if (! filled($this->user_profile)) {
            return asset('images/profile/user-1.jpg');
        }

        if (str_starts_with($this->user_profile, 'http://') || str_starts_with($this->user_profile, 'https://')) {
            return $this->user_profile;
        }

        return Storage::disk('public')->url($this->user_profile);
    }

    private function extraPermissionTableExists(): bool
    {
        static $exists;

        return $exists ??= Schema::hasTable('permission_user');
    }
    public function lectureSessions(): HasMany
    {
        return $this->hasMany(LectureSession::class, 'lecturer_id');
    }

    public function lecturerPayables(): HasMany
    {
        return $this->hasMany(LecturerPayable::class, 'lecturer_id');
    }
    
}