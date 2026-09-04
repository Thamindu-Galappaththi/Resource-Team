<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->slug === 'developer' || $this->slug === 'super_admin') {
            return true;
        }

        return $this->permissions->contains('slug', $slug);
    }

    public function dashboard(): array
    {
        return config('rbac.dashboards.'.$this->slug, [
            'title' => $this->name.' Dashboard',
            'subtitle' => $this->description,
            'widgets' => [],
        ]);
    }
}
