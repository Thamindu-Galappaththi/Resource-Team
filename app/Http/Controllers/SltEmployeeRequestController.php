<?php

namespace App\Http\Controllers;

use App\Enums\CanteenReservationStatus;
use App\Enums\MealType;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\CanteenReservation;
use App\Models\Reservation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SltEmployeeRequestController extends Controller
{
    public function resources(Request $request): View
    {
        $user = $request->user();

        $reservations = Reservation::query()
            ->with(['location'])
            ->where(function ($query) use ($user) {
                $query->where('requester_id', $user->id)
                    ->orWhere('created_by_user_id', $user->id);
            })
            ->whereNotIn('type', [ReservationType::HOSTEL->value, ReservationType::CANTEEN->value])
            ->latest('reservation_date')
            ->latest('id')
            ->paginate(4)
            ->withQueryString();

        return $this->renderRequests($request, [
            'kind' => 'resource',
            'title' => 'My resource requests',
            'subtitle' => 'Reservations you submitted for halls, labs, and equipment.',
            'createUrl' => route('reservations.create'),
            'createLabel' => 'Create Reservation',
            'columns' => ['Reference', 'Request', 'Date', 'Details', 'Status'],
            'requests' => $this->mapPaginator($reservations, function (Reservation $reservation) {
                $status = ReservationStatus::tryFrom((string) $reservation->status);

                return [
                    'title' => $reservation->title ?: 'Resource reservation',
                    'reference' => $reservation->reference,
                    'when' => $this->resourceWhen($reservation),
                    'meta' => $reservation->location?->name,
                    'status' => $status?->label() ?? (string) $reservation->status,
                    'tone' => $this->reservationTone((string) $reservation->status),
                    'href' => route('reservations.show', $reservation),
                ];
            }),
        ]);
    }

    public function hostel(Request $request): View
    {
        $user = $request->user();

        $reservations = Reservation::query()
            ->with(['location', 'hostelStay.roomType'])
            ->where(function ($query) use ($user) {
                $query->where('requester_id', $user->id)
                    ->orWhere('created_by_user_id', $user->id);
            })
            ->where('type', ReservationType::HOSTEL->value)
            ->latest('reservation_date')
            ->latest('id')
            ->paginate(4)
            ->withQueryString();

        return $this->renderRequests($request, [
            'kind' => 'hostel',
            'title' => 'My hostel requests',
            'subtitle' => 'Hostel stays you requested for visitors and staff.',
            'createUrl' => route('hostel.create'),
            'createLabel' => 'Create Hostel Reservation',
            'columns' => ['Reference', 'Guest', 'Stay dates', 'Details', 'Status'],
            'requests' => $this->mapPaginator($reservations, function (Reservation $reservation) {
                $status = ReservationStatus::tryFrom((string) $reservation->status);
                $stay = $reservation->hostelStay;

                return [
                    'title' => $stay?->guest_name ?: ($reservation->title ?: 'Hostel stay'),
                    'reference' => $reservation->reference,
                    'when' => $this->hostelWhen($reservation),
                    'meta' => $stay?->roomType?->name ?? $reservation->location?->name,
                    'status' => $status?->label() ?? (string) $reservation->status,
                    'tone' => $this->reservationTone((string) $reservation->status),
                    'href' => route('hostel.show', $reservation),
                ];
            }),
        ]);
    }

    public function canteen(Request $request): View
    {
        $user = $request->user();

        $reservations = CanteenReservation::query()
            ->with(['location'])
            ->where('requested_by_user_id', $user->id)
            ->latest('reservation_date')
            ->latest('id')
            ->paginate(4)
            ->withQueryString();

        return $this->renderRequests($request, [
            'kind' => 'canteen',
            'title' => 'My canteen requests',
            'subtitle' => 'Meal and catering orders you submitted to the canteen.',
            'createUrl' => route('canteen.create'),
            'createLabel' => 'Create reservation',
            'columns' => ['Reference', 'Request', 'Date', 'Details', 'Status'],
            'requests' => $this->mapPaginator($reservations, function (CanteenReservation $reservation) {
                $status = CanteenReservationStatus::tryFrom((string) $reservation->status);
                $meal = MealType::tryFrom((string) $reservation->meal_type);

                return [
                    'title' => $reservation->reservation_name ?: 'Canteen reservation',
                    'reference' => $reservation->reservation_ref,
                    'when' => $this->canteenWhen($reservation),
                    'meta' => trim(($meal?->label() ?? '').' · '.$reservation->number_of_orders.' orders', ' ·'),
                    'status' => $status?->label() ?? (string) $reservation->status,
                    'tone' => $status?->tone() ?? 'pending',
                    'href' => route('canteen.show', $reservation),
                ];
            }),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderRequests(Request $request, array $data): View
    {
        $view = $request->ajax()
            ? 'dashboards.partials.slt-requests-table'
            : 'dashboards.slt-requests';

        return view($view, $data);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param  LengthAwarePaginator<int, TModel>  $paginator
     * @param  callable(TModel): array<string, mixed>  $mapper
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function mapPaginator(LengthAwarePaginator $paginator, callable $mapper): LengthAwarePaginator
    {
        $paginator->setCollection($paginator->getCollection()->map($mapper)->values());

        return $paginator;
    }

    private function resourceWhen(Reservation $reservation): string
    {
        $date = $reservation->reservation_date?->format('D, d M Y') ?? '';
        $start = substr((string) $reservation->start_time, 0, 5);
        $end = substr((string) $reservation->end_time, 0, 5);

        if ($start !== '' && $end !== '') {
            return trim($date.' · '.$start.'–'.$end);
        }

        return $date;
    }

    private function hostelWhen(Reservation $reservation): string
    {
        $stay = $reservation->hostelStay;
        if ($stay?->check_in_at && $stay->check_out_at) {
            return $stay->check_in_at->timezone(config('reservations.display_timezone', 'Asia/Colombo'))->format('d M Y')
                .' – '
                .$stay->check_out_at->timezone(config('reservations.display_timezone', 'Asia/Colombo'))->format('d M Y');
        }

        return $reservation->reservation_date?->format('D, d M Y') ?? '';
    }

    private function canteenWhen(CanteenReservation $reservation): string
    {
        $date = $reservation->reservation_date?->format('D, d M Y') ?? '';
        $time = substr((string) $reservation->reservation_time, 0, 5);

        return $time !== '' ? trim($date.' · '.$time) : $date;
    }

    private function reservationTone(string $status): string
    {
        return match ($status) {
            ReservationStatus::APPROVED->value, ReservationStatus::CONFIRMED->value => 'approved',
            ReservationStatus::PENDING_APPROVAL->value, ReservationStatus::CHANGES_REQUESTED->value, ReservationStatus::DRAFT->value => 'pending',
            ReservationStatus::REJECTED->value, ReservationStatus::EXPIRED->value => 'rejected',
            ReservationStatus::CANCELLED->value => 'cancelled',
            ReservationStatus::COMPLETED->value => 'completed',
            default => 'pending',
        };
    }
}
