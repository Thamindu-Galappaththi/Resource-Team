<?php

namespace App\Models;

use App\Enums\CanteenReservationStatus;
use App\Enums\MealType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CanteenReservation extends Model
{
    use HasFactory;

    protected $table = 'canteen_reservations';

    protected $fillable = [
        'reservation_ref',
        'reservation_name',
        'requested_by_user_id',
        'location_id',
        'meal_type',
        'reservation_date',
        'reservation_time',
        'number_of_orders',
        'order_details',
        'special_remarks',
        'status',
        'approved_by_user_id',
        'approval_comments',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'reservation_time' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $reservation) {
            if (empty($reservation->reservation_ref)) {
                $reservation->reservation_ref = self::generateReservationRef();
            }

            if ($reservation->number_of_orders !== null && $reservation->number_of_orders > config('canteen.large_group_threshold', 50)) {
                $reservation->status = CanteenReservationStatus::PENDING->value;
            }

            if (empty($reservation->status)) {
                $reservation->status = CanteenReservationStatus::CONFIRMED->value;
            }
        });
    }

    public static function generateReservationRef(): string
    {
        $prefix = 'CN-'.now()->format('Y');
        $latest = self::query()
            ->where('reservation_ref', 'like', $prefix.'-%')
            ->latest('id')
            ->value('reservation_ref');

        $sequence = 1;

        if ($latest) {
            $parts = explode('-', $latest);
            $sequence = (int) ($parts[2] ?? 1);
            $sequence++;
        }

        return sprintf('%s-%04d', $prefix, $sequence);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function isLargeGroupOrder(): bool
    {
        return (int) $this->number_of_orders >= (int) config('canteen.large_group_threshold', 50);
    }

    public function canBeEdited(): bool
    {
        return $this->status === CanteenReservationStatus::PENDING->value;
    }

    public function canBeCancelled(): bool
    {
        if (in_array($this->status, [
            CanteenReservationStatus::REJECTED->value,
            CanteenReservationStatus::CANCELLED->value,
            CanteenReservationStatus::COMPLETED->value,
        ], true)) {
            return false;
        }

        if ($this->status === CanteenReservationStatus::CONFIRMED->value && $this->reservation_date && $this->reservation_date->isPast()) {
            return false;
        }

        return $this->status === CanteenReservationStatus::PENDING->value || $this->status === CanteenReservationStatus::CONFIRMED->value;
    }

    public function isPastReservation(): bool
    {
        if (! $this->reservation_date) {
            return false;
        }

        $reservationDateTime = $this->reservation_date->copy()->setTimeFromTimeString($this->reservation_time ?? '00:00:00');

        return $reservationDateTime->isPast();
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! filled($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('reservation_name', 'like', '%'.$search.'%')
                ->orWhere('reservation_ref', 'like', '%'.$search.'%')
                ->orWhere('order_details', 'like', '%'.$search.'%')
                ->orWhereHas('requestedBy', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
        });
    }

    public function scopeFilter($query, array $filters):
        \Illuminate\Database\Eloquent\Builder
    {
        if (($filters['meal_type'] ?? null) !== null && $filters['meal_type'] !== '') {
            $query->where('meal_type', $filters['meal_type']);
        }

        if (($filters['status'] ?? null) !== null && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (($filters['from_date'] ?? null) !== null && $filters['from_date'] !== '') {
            $query->whereDate('reservation_date', '>=', $filters['from_date']);
        }

        if (($filters['to_date'] ?? null) !== null && $filters['to_date'] !== '') {
            $query->whereDate('reservation_date', '<=', $filters['to_date']);
        }

        return $query;
    }
}
