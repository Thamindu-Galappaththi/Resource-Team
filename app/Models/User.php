<?php

namespace App\Models;

use App\Helpers\RoleHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
            || $this->role?->hasPermission($permission)) {
            return true;
        }

        return $this->roles->pluck('slug')
            ->push($this->roleSlug())
            ->filter()
            ->contains(fn (string $slug) => RoleHelper::hasPermission($slug, $permission));
    }
}
