<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CHANGES_REQUESTED = 'changes_requested';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING_APPROVAL => 'Pending',
            self::CHANGES_REQUESTED => 'Changes requested',
            self::IN_PROGRESS => 'In progress',
            default => str_replace('_', ' ', ucfirst($this->value)),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING_APPROVAL, self::CHANGES_REQUESTED => 'bg-warning text-dark',
            self::APPROVED => 'status-approved',
            self::CONFIRMED => 'bg-success',
            self::REJECTED => 'bg-danger',
            self::IN_PROGRESS => 'bg-info text-dark',
            self::COMPLETED => 'bg-primary',
            self::CANCELLED => 'bg-secondary',
            self::DRAFT, self::EXPIRED => 'bg-light text-dark border',
        };
    }

    public static function blockingItemStatuses(): array
    {
        return [
            ReservationItemStatus::HELD->value,
            ReservationItemStatus::CONFIRMED->value,
        ];
    }

    public static function cancellable(): array
    {
        return [
            self::DRAFT->value,
            self::PENDING_APPROVAL->value,
            self::APPROVED->value,
            self::CONFIRMED->value,
            self::CHANGES_REQUESTED->value,
        ];
    }
}
