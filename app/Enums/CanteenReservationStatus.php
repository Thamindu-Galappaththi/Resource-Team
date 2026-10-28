<?php

namespace App\Enums;

enum CanteenReservationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case CONFIRMED = 'confirmed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    public static function kitchen(): array
    {
        return [self::PENDING->value, ...self::accepted()];
    }

    public static function accepted(): array
    {
        return [self::APPROVED->value, self::CONFIRMED->value];
    }

    public static function calendarActive(): array
    {
        return [self::PENDING->value, ...self::accepted()];
    }

    public function label(): string
    {
        return $this === self::CONFIRMED ? 'Approved' : ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::APPROVED, self::CONFIRMED => 'bg-success-subtle text-success',
            self::PENDING => 'bg-warning-subtle text-dark',
            self::REJECTED, self::CANCELLED => 'bg-danger-subtle text-danger',
            self::COMPLETED => 'bg-secondary-subtle text-secondary',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::APPROVED, self::CONFIRMED => 'approved',
            self::PENDING => 'pending',
            self::REJECTED => 'rejected',
            self::CANCELLED => 'cancelled',
            self::COMPLETED => 'completed',
        };
    }
}
