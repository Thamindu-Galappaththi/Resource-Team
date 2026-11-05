<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    public const CAMPUS_ORDER = [
        'Welisara',
        'Moratuwa',
        'Peradeniya',
    ];

    protected $fillable = [
        'name',
    ];

    /**
     * Campus dropdown order: Welisara, Moratuwa, Peradeniya, then any others A–Z.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE name WHEN 'Welisara' THEN 1 WHEN 'Moratuwa' THEN 2 WHEN 'Peradeniya' THEN 3 ELSE 99 END")
            ->orderBy('name');
    }

    /** Campuses where resources can be added (config/resources.php). */
    public function scopeForResources(Builder $query): Builder
    {
        return $query->whereIn('name', config('resources.locations', []));
    }

    /**
     * A location can have many resources assigned to it.
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}