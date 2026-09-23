@extends('layouts.app')

@section('title', 'Edit Canteen Reservation')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0">
            <h4 class="mb-0">Edit Canteen Reservation</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('canteen.reservations.update', $reservation) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Reservation Name</label>
                        <input type="text" class="form-control @error('reservation_name') is-invalid @enderror" name="reservation_name" value="{{ old('reservation_name', $reservation->reservation_name) }}" required>
                        @error('reservation_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Meal Type</label>
                        <select class="form-select @error('meal_type') is-invalid @enderror" name="meal_type" required>
                            @foreach(\App\Enums\MealType::values() as $mealType)
                                <option value="{{ $mealType }}" {{ old('meal_type', $reservation->meal_type) === $mealType ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $mealType)) }}</option>
                            @endforeach
                        </select>
                        @error('meal_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reservation Date</label>
                        <input type="date" class="form-control @error('reservation_date') is-invalid @enderror" name="reservation_date" value="{{ old('reservation_date', $reservation->reservation_date->format('Y-m-d')) }}" required>
                        @error('reservation_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reservation Time</label>
                        <input type="time" class="form-control @error('reservation_time') is-invalid @enderror" name="reservation_time" value="{{ old('reservation_time', $reservation->reservation_time) }}" required>
                        @error('reservation_time')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Number of Orders</label>
                        <input type="number" min="1" class="form-control @error('number_of_orders') is-invalid @enderror" name="number_of_orders" value="{{ old('number_of_orders', $reservation->number_of_orders) }}" required>
                        @error('number_of_orders')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Order Details</label>
                        <textarea class="form-control @error('order_details') is-invalid @enderror" name="order_details" rows="4">{{ old('order_details', $reservation->order_details) }}</textarea>
                        @error('order_details')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Special Remarks</label>
                        <textarea class="form-control @error('special_remarks') is-invalid @enderror" name="special_remarks" rows="3">{{ old('special_remarks', $reservation->special_remarks) }}</textarea>
                        @error('special_remarks')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">Update Reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
