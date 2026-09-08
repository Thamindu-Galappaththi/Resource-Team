@extends('layouts.app')

@section('title', 'Canteen Reservations')

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Canteen Reservations</h4>
                <a href="{{ route('canteen.reservations.create') }}" class="btn btn-primary">+ Create Reservation</a>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('canteen.reservations.index') }}" class="row g-2">
                        <div class="col-md-4">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search code or name">
                        </div>
                        <div class="col-md-2">
                            <select name="canteen_id" class="form-select">
                                <option value="">All Canteens</option>
                                @foreach($canteens as $c)
                                    <option value="{{ $c->id }}" {{ request('canteen_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="reservation_date" value="{{ request('reservation_date') }}" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <select name="meal_type" class="form-select">
                                <option value="">All Meals</option>
                                @foreach(['Breakfast','Morning Tea','Lunch','Evening Tea','Dinner','Other'] as $m)
                                    <option value="{{ $m }}" {{ request('meal_type') == $m ? 'selected' : '' }}>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                @foreach(['pending','confirmed','cancelled','completed'] as $s)
                                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 mt-2">
                            <button class="btn btn-primary">Filter</button>
                            <a href="{{ route('canteen.reservations.index') }}" class="btn btn-outline-secondary ms-2">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if($reservations->count() === 0)
                        <p class="text-muted">No canteen reservations found.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Reservation ID</th>
                                        <th>Name</th>
                                        <th>Canteen</th>
                                        <th>Location</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Meal Type</th>
                                        <th>No. Orders</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reservations as $r)
                                        <tr>
                                            <td>{{ $r->reservation_code }}</td>
                                            <td>{{ $r->reservation_name }}</td>
                                            <td>{{ $r->canteen?->name }}</td>
                                            <td>{{ $r->canteen?->location?->name }}</td>
                                            <td>{{ $r->reservation_date->format('Y-m-d') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($r->reservation_time)->format('H:i') }}</td>
                                            <td>{{ $r->meal_type }}</td>
                                            <td>{{ $r->number_of_orders }}</td>
                                            <td><span class="badge bg-secondary">{{ ucfirst($r->status) }}</span></td>
                                            <td>{{ $r->creator?->name }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">{{ $reservations->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
