<?php

namespace App\Models;

use App\Enums\HostelStudentRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelStudentRequest extends Model
{
    public const REFERENCE_PREFIX = 'HSR';

    protected $fillable = [
        'reference',
        'student_name',
        'student_id',
        'check_in_date',
        'check_in_time',
        'check_out_date',
        'check_out_time',
        'home_town',
        'phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'status',
        'decision_note',
        'decided_by_user_id',
        'decided_at',
        'sms_status',
        'sms_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'decided_at' => 'datetime',
            'sms_sent_at' => 'datetime',
            'status' => HostelStudentRequestStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $request) {
            $request->reference ??= self::generateReference();
            $request->status ??= HostelStudentRequestStatus::PENDING;
        });
    }

    public static function generateReference(): string
    {
        $prefix = self::REFERENCE_PREFIX.'-'.now()->format('Y');
        $latest = self::query()
            ->where('reference', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('reference');

        $sequence = $latest ? ((int) substr($latest, strrpos($latest, '-') + 1)) + 1 : 1;

        return sprintf('%s-%06d', $prefix, $sequence);
    }

    public function checkInLabel(): string
    {
        return $this->check_in_date?->format('d M Y').' '.substr((string) $this->check_in_time, 0, 5);
    }

    public function checkOutLabel(): string
    {
        return $this->check_out_date?->format('d M Y').' '.substr((string) $this->check_out_time, 0, 5);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! filled($search)) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($search) {
            foreach (['reference', 'student_name', 'student_id', 'phone', 'home_town'] as $column) {
                $inner->orWhere($column, 'like', '%'.$search.'%');
            }
        });
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }
}
