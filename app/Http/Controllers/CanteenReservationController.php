<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCanteenReservationRequest;
use App\Models\Canteen;
use App\Models\CanteenReservation;
use Illuminate\Http\Request;

class CanteenReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = CanteenReservation::query()->with(['canteen.location', 'creator']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reservation_code', 'like', "%{$search}%")
                    ->orWhere('reservation_name', 'like', "%{$search}%");
            });
        }

        if ($canteen = $request->input('canteen_id')) {
            $query->where('canteen_id', $canteen);
        }

        if ($date = $request->input('reservation_date')) {
            $query->whereDate('reservation_date', $date);
        }

        if ($meal = $request->input('meal_type')) {
            $query->where('meal_type', $meal);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $reservations = $query->orderByDesc('reservation_date')->paginate(15)->withQueryString();

        $canteens = Canteen::where('is_active', true)->orderBy('name')->get();

        return view('canteen_reservations.index', compact('reservations', 'canteens'));
    }

    public function create()
    {
        $canteens = Canteen::where('is_active', true)->with('location')->orderBy('name')->get();
        $mealTypes = ['Breakfast','Morning Tea','Lunch','Evening Tea','Dinner','Other'];
        return view('canteen_reservations.create', compact('canteens', 'mealTypes'));
    }

    public function store(StoreCanteenReservationRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        // Use temp code to satisfy unique constraint, then update
        $data['reservation_code'] = 'TEMP-'.uniqid();

        $reservation = CanteenReservation::create($data);

        $reservation->reservation_code = 'CR-'.str_pad($reservation->id, 5, '0', STR_PAD_LEFT);
        $reservation->save();

        return redirect()->route('canteen.reservations.index')->with('status', 'Canteen reservation created successfully.');
    }
}
