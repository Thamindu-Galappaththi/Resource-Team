<?php

namespace App\Policies;

use App\Enums\CanteenReservationStatus;
use App\Models\CanteenReservation;
use App\Models\User;

class CanteenReservationPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('developer', 'super_admin', 'admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('developer', 'super_admin', 'admin', 'coordinator', 'canteen', 'slt_employee')
            || $user->hasPermission('canteen.view');
    }

    public function view(User $user, CanteenReservation $reservation): bool
    {
        if ($user->hasRole('developer', 'super_admin', 'admin', 'canteen')) {
            return true;
        }

        return $user->id === $reservation->requested_by_user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin', 'admin', 'coordinator', 'slt_employee')
            || $user->hasPermission('canteen.create');
    }

    public function update(User $user, CanteenReservation $reservation): bool
    {
        if ($user->hasRole('super_admin', 'admin', 'developer')) {
            return true;
        }

        return $user->id === $reservation->requested_by_user_id
            && $reservation->status === CanteenReservationStatus::PENDING->value;
    }

    public function delete(User $user, CanteenReservation $reservation): bool
    {
        return $user->hasRole('super_admin', 'admin', 'developer');
    }

    public function cancel(User $user, CanteenReservation $reservation): bool
    {
        return $user->id === $reservation->requested_by_user_id
            && $reservation->canBeCancelled();
    }

    public function manageStatus(User $user, CanteenReservation $reservation): bool
    {
        return $user->hasRole('canteen')
            && ! in_array($reservation->status, [
                CanteenReservationStatus::REJECTED->value,
                CanteenReservationStatus::CANCELLED->value,
                CanteenReservationStatus::COMPLETED->value,
            ], true);
    }
}
