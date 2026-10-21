<?php

namespace App\Services;

use App\Enums\ReservationType;
use App\Models\DatabaseNotification;
use Illuminate\Support\Str;

class NotificationPresenter
{
    public const MESSAGE_LIMIT = 90;

    private const ICONS = [
        'NewUserCreated' => 'ti-user-plus',
        'ReservationCreated' => 'ti-calendar-plus',
        'NewReservationPending' => 'ti-calendar-time',
        'ReservationCancelled' => 'ti-calendar-off',
        'ReservationDecision' => 'ti-calendar-event',
        'CanteenReservationCreated' => 'ti-tools-kitchen-2',
        'NewCanteenReservationPending' => 'ti-tools-kitchen-2',
        'CanteenReservationStatusUpdated' => 'ti-tools-kitchen-2',
    ];

    private const TITLES = [
        'NewUserCreated' => 'New user account',
        'ReservationCreated' => 'Reservation submitted',
        'NewReservationPending' => 'Approval needed',
        'ReservationCancelled' => 'Reservation cancelled',
        'ReservationDecision' => 'Reservation update',
        'CanteenReservationCreated' => 'Canteen reservation',
        'NewCanteenReservationPending' => 'Canteen approval needed',
        'CanteenReservationStatusUpdated' => 'Canteen reservation update',
    ];

    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     message: string,
     *     full_message: string,
     *     icon: string,
     *     url: string|null,
     *     time: string|null,
     *     created_at: string|null,
     *     unread: bool,
     *     read_url: string,
     *     unread_url: string,
     *     delete_url: string
     * }
     */
    public static function toArray(DatabaseNotification $notification): array
    {
        $type = class_basename($notification->type);
        $data = $notification->data;
        $fullMessage = trim((string) ($data['message'] ?? 'You have a new notification.'));

        return [
            'id' => $notification->id,
            'title' => $data['title'] ?? self::TITLES[$type] ?? 'Notification',
            'message' => Str::limit($fullMessage, self::MESSAGE_LIMIT),
            'full_message' => $fullMessage,
            'icon' => $data['icon'] ?? self::ICONS[$type] ?? 'ti-bell',
            'url' => self::url($notification),
            'time' => $notification->created_at?->diffForHumans(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'unread' => $notification->read_at === null,
            'read_url' => route('notifications.read', $notification->id),
            'unread_url' => route('notifications.unread', $notification->id),
            'delete_url' => route('notifications.destroy', $notification->id),
        ];
    }

    public static function url(DatabaseNotification $notification): ?string
    {
        $data = $notification->data;
        $type = class_basename($notification->type);
        $reservationId = $data['reservation_id'] ?? null;
        $reservationType = $data['reservation_type'] ?? null;
        $reference = (string) ($data['reference'] ?? '');

        $knownUrl = match ($type) {
            'NewUserCreated' => route('user.management'),
            'ReservationCreated', 'ReservationCancelled', 'ReservationDecision' => self::reservationShowUrl($reservationId, $reservationType, $reference),
            'NewReservationPending' => self::pendingUrl($reservationId, $reservationType, $reference),
            'CanteenReservationCreated', 'NewCanteenReservationPending', 'CanteenReservationStatusUpdated' => $reservationId
                ? route('canteen.show', $reservationId)
                : route('canteen.index'),
            default => null,
        };

        if ($knownUrl !== null) {
            return $knownUrl;
        }

        return filled($data['url'] ?? null) ? $data['url'] : null;
    }

    private static function isHostel(?string $type, string $reference): bool
    {
        return $type === ReservationType::HOSTEL->value || str_starts_with($reference, 'HST');
    }

    private static function reservationShowUrl(mixed $id, ?string $type, string $reference): string
    {
        if (! $id) {
            return self::isHostel($type, $reference) ? route('hostel.index') : route('reservations.index');
        }

        return self::isHostel($type, $reference)
            ? route('hostel.show', $id)
            : route('reservations.show', $id);
    }

    private static function pendingUrl(mixed $id, ?string $type, string $reference): string
    {
        if (self::isHostel($type, $reference)) {
            return $id ? route('hostel.show', $id) : route('hostel.index');
        }

        return $reference !== ''
            ? route('approvals.index', ['search' => $reference])
            : route('approvals.index');
    }
}
