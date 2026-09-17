<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Models\Reservation;
use App\Models\Resource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(): View
    {
        $primaryReservations = Reservation::query()
            ->whereHas('resource', function ($query) {
                $query->where('serial_number', 'not like', 'ADDON-%');
            });

        $reservations = (clone $primaryReservations)
            ->with(['user', 'resource.type.category', 'resource.location'])
            ->orderByDesc('reservation_date')
            ->orderByDesc('created_at')
            ->paginate(10);

        $totalCount = (clone $primaryReservations)->count();
        $pendingCount = (clone $primaryReservations)->where('status', 'pending')->count();
        $rejectedCount = (clone $primaryReservations)->whereIn('status', ['rejected', 'declined'])->count();

        return view('reservations.index', [
            'reservations' => $reservations,
            'totalCount' => $totalCount,
            'pendingCount' => $pendingCount,
            'rejectedCount' => $rejectedCount,
        ]);
    }

    public function create(): View
    {
        $categories = \App\Models\ResourceCategory::orderBy('name')->get();
        return view('reservations.create', ['categories' => $categories]);
    }

    public function availableResources(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reservation_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'location_id' => ['required', 'integer'],
            'resource_category_id' => ['nullable', 'integer'],
        ]);

        $resources = Resource::with(['type.category', 'type.featureValues.feature', 'location'])
            ->where('location_id', $validated['location_id'])
            ->where('status', 'active')
            ->when($validated['resource_category_id'] ?? null, function ($query, $categoryId) {
                $query->whereHas('type', fn ($typeQuery) => $typeQuery->where('resource_category_id', $categoryId));
            })
            ->whereDoesntHave('reservations', function ($query) use ($validated) {
                $query->whereDate('reservation_date', $validated['reservation_date'])
                    ->whereIn('status', Reservation::BLOCKING_STATUSES)
                    ->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })
            ->orderBy('name_model')
            ->get();

        return response()->json($resources);
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $reservation = DB::transaction(function () use ($validated, $request) {
            $primaryResource = Resource::with('type')
                ->whereKey($validated['resource_id'])
                ->where('location_id', $validated['location_id'])
                ->where('status', 'active')
                ->where('serial_number', 'not like', 'ADDON-%')
                ->first();

            if (!$primaryResource || (int) $primaryResource->type->resource_category_id !== (int) $validated['resource_category_id']) {
                abort(422, 'The selected resource does not match the requested category and location.');
            }

            $resourceIds = collect($validated['add_ons'] ?? [])
                ->pluck('resource_id')
                ->prepend($validated['resource_id'])
                ->values();

            foreach ($resourceIds as $resourceId) {
                $resource = Resource::query()
                    ->whereKey($resourceId)
                    ->where('location_id', $validated['location_id'])
                    ->where('status', 'active')
                    ->first();

                if (!$resource || $resourceIds->filter(fn ($id) => (int) $id === (int) $resource->id)->count() > 1) {
                    abort(422, 'One of the selected resources is invalid or duplicated.');
                }

                $conflict = Reservation::query()
                    ->where('resource_id', $resourceId)
                    ->whereDate('reservation_date', $validated['reservation_date'])
                    ->whereIn('status', Reservation::BLOCKING_STATUSES)
                    ->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time'])
                    ->lockForUpdate()
                    ->exists();

                if ($conflict) {
                    abort(422, 'One of the selected resources is no longer available for this time.');
                }
            }

            $reservation = Reservation::create([
                'user_id' => $request->user()->id,
                'resource_id' => $validated['resource_id'],
                'location_id' => $validated['location_id'],
                'reservation_date' => $validated['reservation_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'special_requirements' => $validated['special_requirements'] ?? null,
                'status' => 'pending',
            ]);

            foreach ($validated['add_ons'] ?? [] as $addOn) {
                $reservation->addOns()->create([
                    'resource_id' => $addOn['resource_id'],
                    'special_remarks' => $addOn['special_remarks'] ?? null,
                ]);
            }

            return $reservation->load(['resource.type.category', 'resource.location', 'addOns.resource']);
        });

        return response()->json($reservation, 201);
    }
}