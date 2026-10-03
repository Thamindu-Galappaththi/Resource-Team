<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($ability !== 'manageHostel' && $user->hasRole('super_admin', 'admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('reservations.index')
            || $user->hasPermission('reservations.calendar');
    }

    public function createHostel(User $user): bool
    {
        return $user->hasPermission('hostel.create');
    }

    public function viewAnyHostel(User $user): bool
    {
        return $user->hasPermission('hostel.index');
    }

    public function viewHostel(User $user, Reservation $reservation): bool
    {
        if ($reservation->type !== \App\Enums\ReservationType::HOSTEL->value) {
            return false;
        }

        if ($user->hasRole('coordinator', 'hostel_manager') || $user->hasPermission('hostel.manage')) {
            return true;
        }

        return $user->id === $reservation->requester_id
            || $user->id === $reservation->created_by_user_id;
    }

    public function cancelHostel(User $user, Reservation $reservation): bool
    {
        if ($reservation->type !== \App\Enums\ReservationType::HOSTEL->value || ! $reservation->canBeCancelled()) {
            return false;
        }

        if ($user->hasRole('coordinator', 'hostel_manager') || $user->hasPermission('hostel.manage')) {
            return true;
        }

        return $user->id === $reservation->requester_id
            || $user->id === $reservation->created_by_user_id;
    }

    public function manageHostel(User $user, Reservation $reservation): bool
    {
        return $reservation->type === \App\Enums\ReservationType::HOSTEL->value
            && $reservation->status === \App\Enums\ReservationStatus::PENDING_APPROVAL->value
            && ($user->hasRole('hostel_manager') || $user->hasPermission('hostel.manage'));
    }

    public function view(User $user, Reservation $reservation): bool
    {
        if ($user->hasRole('coordinator', 'resource_owner')) {
            return true;
        }

        return $user->id === $reservation->requester_id
            || $user->id === $reservation->created_by_user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('reservations.create');
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        if (! $reservation->canBeCancelled()) {
            return false;
        }

        if ($user->hasRole('coordinator', 'resource_owner')) {
            return true;
        }

        return $user->id === $reservation->requester_id
            || $user->id === $reservation->created_by_user_id;
    }
}
