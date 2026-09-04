<?php

namespace App\Models;

use App\Helpers\RoleHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function roleSlug(): string
    {
        return $this->role?->slug ?? RoleHelper::normalizeRole($this->user_role);
    }

    public function hasRole(string ...$roles): bool
    {
        $slug = $this->roleSlug();

        foreach ($roles as $role) {
            if ($slug === RoleHelper::normalizeRole($role)) {
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

        $this->loadMissing('role.permissions');

        if ($this->role?->hasPermission($permission)) {
            return true;
        }

        return RoleHelper::hasPermission($this->roleSlug(), $permission);
    }
}
