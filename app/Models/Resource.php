<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Resource extends Model
{
    use HasFactory;

    /**
     * The fixed set of allowed values for the "status" column/dropdown.
     * Kept as a class constant so validation (StoreResourceRequest),
     * the controller, and any future admin screen all reference this
     * ONE list instead of duplicating the same strings in multiple
     * places and risking them drifting out of sync.
     */
    public const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'under_maintenance' => 'Under Maintenance',
        'decommissioned' => 'Decommissioned',
        'pending_deletion' => 'Pending Deletion',
        'deleted' => 'Deleted',
    ];

    /**
     * Mass-assignable fields — mirrors the Tab 3 form fields:
     * resource_type_id, location_id, name_model, serial_number, status.
     * Note: category_id is intentionally NOT here — see the migration
     * comment for why (it's derived through resource_type, not stored).
     * Note: no resource_owner_id — that's just a hardcoded name in the
     * Tab 3 dropdown right now, there's no table/column for it yet.
     */
    protected $fillable = [
        'resource_type_id',
        'location_id',
        'name_model',
        'serial_number',
        'status',
    ];

    /**
     * Inverse of ResourceType::resources(). Each resource belongs to
     * exactly one type (the "Type" dropdown on Tab 3).
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class, 'resource_type_id');
    }

    /**
     * Inverse of Location::resources(). Each resource is assigned to
     * exactly one location (the "Location" dropdown on Tab 3).
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Lets you call $resource->category to get the parent category
     * WITHOUT storing a redundant category_id column, and without an
     * extra query per resource (Eloquent can eager-load this "through"
     * relation the same as any normal one, e.g. Resource::with('category')).
     *
     * Path: resources.resource_type_id -> resource_types.id
     *       resource_types.resource_category_id -> resource_categories.id
     */
    public function category(): HasOneThrough
    {
        return $this->hasOneThrough(
            ResourceCategory::class,   // final model we want to reach
            ResourceType::class,       // intermediate model
            'id',                      // FK on resource_types that links to... (local key on ResourceType)
            'id',                      // FK on resource_categories that links to... (local key on ResourceCategory)
            'resource_type_id',        // local key on THIS (resources) table
            'resource_category_id'     // local key on the intermediate (resource_types) table
        );
    }
}