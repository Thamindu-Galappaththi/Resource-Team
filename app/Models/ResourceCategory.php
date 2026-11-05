<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResourceCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Mass-assignable fields.
     * Only "name" — features are created/saved separately (see
     * ResourceCategoryController@store), not passed in directly here.
     */
    protected $fillable = [
        'name',
    ];

    protected $appends = ['is_locked'];

    public static function isHostelName(?string $name): bool
    {
        return strcasecmp(trim((string) $name), (string) config('hostel.category_name', 'Hostel Room')) === 0;
    }

    /** The Hostel Room category drives hostel bookings, so it cannot be renamed or deleted. */
    public function isLocked(): bool
    {
        return self::isHostelName($this->name);
    }

    public function getIsLockedAttribute(): bool
    {
        return $this->isLocked();
    }

    /**
     * A category has many "Additional Features" rows (Tab 1's
     * repeater). One-to-many: one category -> many features.
     */
    public function features(): HasMany
    {
        return $this->hasMany(CategoryFeature::class);
    }

    /**
     * A category has many resource types (Tab 2 rows created under it).
     * One-to-many: one category -> many types.
     */
    public function types(): HasMany
    {
        return $this->hasMany(ResourceType::class, 'resource_category_id');
    }
}
