@extends('layouts.app')

@section('title', 'Create Hostel Reservation')

@section('content')
    <x-module-placeholder
        title="Create Hostel Reservation"
        description="Authorized users will create hostel reservations here by entering guest details, room category, and check-in / check-out dates. Availability checks will prevent double bookings."
    />
@endsection
