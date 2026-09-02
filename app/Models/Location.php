<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * A location can have many resources assigned to it.
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}