<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LecturerProfile extends Model
{
    protected $fillable = [
        'resource_id',
        'name',
        'service_id',
        'nic',
        'email',
        'phone',
        'designation',
        'slt_employee',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'slt_employee' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}