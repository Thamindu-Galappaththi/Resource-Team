@extends('layouts.app')

@section('title', 'Canteen Forecast')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Forecast for {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</h2>
            <p class="text-muted mb-0">Reservation details for the selected date.</p>
        </div>
        <a href="{{ route('canteen.dashboard') }}" class="btn btn-outline-secondary">Back to dashboard</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            @if($records->isEmpty())
                <div class="alert alert-info mb-0">No canteen reservations are scheduled for this day.</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Reservation</th>
                                <th>Meal</th>
                                <th>Time</th>
                                <th>Orders</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $reservation)
                                <tr>
                                    <td>{{ $reservation->reservation_name }}</td>
                                    <td>{{ ucfirst($reservation->meal_type) }}</td>
                                    <td>{{ \Illuminate\Support\Facades\Date::parse($reservation->reservation_time)->format('h:i A') }}</td>
                                    <td>{{ $reservation->number_of_orders }}</td>
                                    <td><span class="badge bg-secondary">{{ $reservation->status }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
