<?php

namespace App\Models;

use App\Helpers\RoleHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'service_id',
        'slt_employee',
        'nic',
        'email',
        'phone',
        'location',
        'password',
        'user_role',
        'role_id',
        'designation',
        'user_type',
        'user_profile',
        'is_active',
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

    public function hasRole(string ...$roles): bool
    {
        $assignedRoles = $this->roles()->pluck('slug')
            ->push($this->roleSlug())
            ->filter()
            ->map(fn (string $slug) => RoleHelper::normalizeRole($slug));

        foreach ($roles as $role) {
            if ($assignedRoles->contains(RoleHelper::normalizeRole($role))) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $this->loadMissing('role.permissions', 'roles.permissions');

        if ($this->roles->contains(fn (Role $role) => $role->hasPermission($permission))
            || $this->role?->hasPermission($permission)
            || ($this->extraPermissionTableExists()
                && $this->loadMissing('extraPermissions')->extraPermissions->contains('slug', $permission))) {
            return true;
        }

        return $this->roles->pluck('slug')
            ->push($this->roleSlug())
            ->filter()
            ->contains(fn (string $slug) => RoleHelper::hasPermission($slug, $permission));
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
}
