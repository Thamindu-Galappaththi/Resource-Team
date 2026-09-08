@extends('layouts.app')

@section('title', 'Create Canteen Reservation')

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-4">Create Canteen Reservation</h4>

                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('canteen.reservations.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="reservation_name" class="form-label">Reservation Name *</label>
                            <input id="reservation_name" name="reservation_name" value="{{ old('reservation_name') }}" class="form-control @error('reservation_name') is-invalid @enderror" required>
                            @error('reservation_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="canteen_id" class="form-label">Canteen *</label>
                            <select id="canteen_id" name="canteen_id" class="form-select @error('canteen_id') is-invalid @enderror" required>
                                <option value="" selected disabled>-- Select Canteen --</option>
                                @foreach($canteens as $c)
                                    <option value="{{ $c->id }}" {{ old('canteen_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} - {{ optional($c->location)->name }}</option>
                                @endforeach
                            </select>
                            @error('canteen_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reservation_date" class="form-label">Date *</label>
                                <input id="reservation_date" name="reservation_date" type="date" value="{{ old('reservation_date') }}" class="form-control @error('reservation_date') is-invalid @enderror" required>
                                @error('reservation_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="reservation_time" class="form-label">Time *</label>
                                <input id="reservation_time" name="reservation_time" type="time" value="{{ old('reservation_time') }}" class="form-control @error('reservation_time') is-invalid @enderror" required>
                                @error('reservation_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="meal_type" class="form-label">Meal Type *</label>
                            <select id="meal_type" name="meal_type" class="form-select @error('meal_type') is-invalid @enderror" required>
                                <option value="" selected disabled>-- Select Meal --</option>
                                @foreach($mealTypes as $meal)
                                    <option value="{{ $meal }}" {{ old('meal_type') == $meal ? 'selected' : '' }}>{{ $meal }}</option>
                                @endforeach
                            </select>
                            @error('meal_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="number_of_orders" class="form-label">Number of Orders *</label>
                            <input id="number_of_orders" name="number_of_orders" type="number" min="1" value="{{ old('number_of_orders', 1) }}" class="form-control @error('number_of_orders') is-invalid @enderror" required>
                            @error('number_of_orders')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="order_details" class="form-label">Order Details *</label>
                            <textarea id="order_details" name="order_details" class="form-control @error('order_details') is-invalid @enderror" required>{{ old('order_details') }}</textarea>
                            @error('order_details')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="special_requirements" class="form-label">Special Requirements</label>
                            <textarea id="special_requirements" name="special_requirements" class="form-control @error('special_requirements') is-invalid @enderror">{{ old('special_requirements') }}</textarea>
                            @error('special_requirements')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button class="btn btn-primary">Create Reservation</button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
