<?php

namespace App\Models;

use App\Enums\ReservationItemStatus;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'reference',
        'requester_id',
        'created_by_user_id',
        'location_id',
        'type',
        'title',
        'description',
        'purpose',
        'attendee_count',
        'reservation_date',
        'start_time',
        'end_time',
        'status',
        'submitted_at',
        'approved_at',
        'cancelled_at',
        'cancellation_reason',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $reservation) {
            if (empty($reservation->public_id)) {
                $reservation->public_id = (string) Str::ulid();
            }

            if (empty($reservation->type)) {
                $reservation->type = ReservationType::STANDARD->value;
            }

            if (empty($reservation->reference)) {
                $code = $reservation->type === ReservationType::HOSTEL->value
                    ? config('hostel.reference_prefix', 'HST')
                    : null;
                $reservation->reference = self::generateReference($code);
            }

            if (empty($reservation->status)) {
                $reservation->status = ReservationStatus::DRAFT->value;
            }

            if (empty($reservation->title) && ! empty($reservation->purpose)) {
                $reservation->title = $reservation->purpose;
            }
        });
    }

    public static function generateReference(?string $code = null): string
    {
        $prefix = ($code ?? config('reservations.reference_prefix', 'RRS')).'-'.now()->format('Y');
        $latest = self::query()
            ->where('reference', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('reference');

        $sequence = 1;
        if ($latest) {
            $parts = explode('-', $latest);
            $sequence = ((int) ($parts[2] ?? 0)) + 1;
        }

        return sprintf('%s-%06d', $prefix, $sequence);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function hostelStay(): HasOne
    {
        return $this->hasOne(HostelStayDetail::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ReservationStatusHistory::class)->orderBy('created_at');
    }

    public function statusEnum(): ReservationStatus
    {
        return ReservationStatus::from($this->status);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ReservationStatus::cancellable(), true);
    }

    public function resourceNames(): string
    {
        return $this->items
            ->pluck('resource_name_snapshot')
            ->filter()
            ->unique()
            ->implode(', ');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! filled($search)) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($search) {
            $inner->where('reference', 'like', '%'.$search.'%')
                ->orWhere('title', 'like', '%'.$search.'%')
                ->orWhere('purpose', 'like', '%'.$search.'%')
                ->orWhereHas('requester', fn (Builder $user) => $user->where('name', 'like', '%'.$search.'%'))
                ->orWhereHas('hostelStay', fn (Builder $stay) => $stay->where('guest_name', 'like', '%'.$search.'%'))
                ->orWhereHas('items', fn (Builder $item) => $item->where('resource_name_snapshot', 'like', '%'.$search.'%'));
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (($filters['status'] ?? null) !== null && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (($filters['location_id'] ?? null) !== null && $filters['location_id'] !== '') {
            $query->where('location_id', $filters['location_id']);
        }

        if (($filters['from_date'] ?? null) !== null && $filters['from_date'] !== '') {
            $query->whereDate('reservation_date', '>=', $filters['from_date']);
        }

        if (($filters['to_date'] ?? null) !== null && $filters['to_date'] !== '') {
            $query->whereDate('reservation_date', '<=', $filters['to_date']);
        }

        if (($filters['resource_id'] ?? null) !== null && $filters['resource_id'] !== '') {
            $query->whereHas('items', fn (Builder $item) => $item->where('resource_id', $filters['resource_id']));
        }

        if (($filters['resource_category_id'] ?? null) !== null && $filters['resource_category_id'] !== '') {
            $query->whereHas('items.resource.type', function (Builder $type) use ($filters) {
                $type->where('resource_category_id', $filters['resource_category_id']);
            });
        }

        if (($filters['room_type_id'] ?? null) !== null && $filters['room_type_id'] !== '') {
            $query->whereHas('hostelStay', fn (Builder $stay) => $stay->where('room_type_id', $filters['room_type_id']));
        }

        if (($filters['check_in_from'] ?? null) !== null && $filters['check_in_from'] !== '') {
            $query->whereHas('hostelStay', fn (Builder $stay) => $stay->whereDate('check_in_at', '>=', $filters['check_in_from']));
        }

        if (($filters['check_in_to'] ?? null) !== null && $filters['check_in_to'] !== '') {
            $query->whereHas('hostelStay', fn (Builder $stay) => $stay->whereDate('check_in_at', '<=', $filters['check_in_to']));
        }

        return $query;
    }
}
