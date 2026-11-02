<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HostelRequestLink extends Model
{
    protected $fillable = ['token', 'created_by_user_id'];

    /**
     * The share link is created once and reused so the URL students receive never changes.
     */
    public static function current(): ?self
    {
        return self::query()->oldest('id')->first();
    }

    public static function firstOrGenerate(User $actor): self
    {
        return self::current() ?? self::query()->create([
            'token' => Str::random(40),
            'created_by_user_id' => $actor->id,
        ]);
    }

    public function url(): string
    {
        return route('hostel.student-form', $this->token);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
