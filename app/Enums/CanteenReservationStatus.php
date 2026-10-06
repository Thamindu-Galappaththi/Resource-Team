<?php

namespace App\Enums;

enum CanteenReservationStatus: string
{
    case PENDING = 'pending';
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
        return [self::PENDING->value, self::CONFIRMED->value];
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::CONFIRMED => 'bg-success-subtle text-success',
            self::PENDING => 'bg-warning-subtle text-dark',
            self::REJECTED, self::CANCELLED => 'bg-danger-subtle text-danger',
            self::COMPLETED => 'bg-secondary-subtle text-secondary',
        };
    }
}
