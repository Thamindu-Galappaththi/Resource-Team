@extends('layouts.app')

@section('title', 'Create Canteen Reservation')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <h1 class="h3 text-white mb-2">Create Canteen Reservation</h1>
            <p class="text-white-50 mb-0">Enter the service details and expected order count.</p>
        </div>
        <a href="{{ route('canteen.index') }}" class="btn btn-outline-light"><i class="ti ti-arrow-left me-1"></i>All reservations</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" role="alert"><strong>Please check the form.</strong> Some details need your attention.</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h2 class="h5 mb-1">Reservation request</h2>
            <p class="text-muted small mb-0">Requests remain pending until reviewed by the canteen team.</p>
        </div>
        <div class="card-body p-4">
            <form id="canteenReservationForm" method="POST" action="{{ route('canteen.store') }}">
                @include('canteen.reservations._form')
                <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 pt-4 mt-2 border-top">
                    <a href="{{ route('canteen.index') }}" class="btn btn-outline-secondary">Discard</a>
                    <button id="canteenSubmitButton" type="submit" class="btn btn-primary">
                        <span class="submit-label"><i class="ti ti-check me-1" aria-hidden="true"></i>Submit reservation</span>
                        <span class="submit-loading d-none"><span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Submitting...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('canteenReservationForm');
        const button = document.getElementById('canteenSubmitButton');
        const date = document.getElementById('reservation_date');
        const orders = document.getElementById('number_of_orders');

        if (!form || !button) return;

        const validateField = (field) => {
            field.classList.toggle('is-invalid', !field.validity.valid);
            field.classList.toggle('is-valid', field.validity.valid && field.value !== '');
        };

        form.querySelectorAll('input, select, textarea').forEach((field) => {
            field.addEventListener('input', () => validateField(field));
            field.addEventListener('change', () => validateField(field));
        });

        form.addEventListener('submit', (event) => {
            if (date.value < date.min || Number(orders.value) < 1 || Number(orders.value) > 10000) {
                event.preventDefault();
                form.reportValidity();
                return;
            }

            button.disabled = true;
            button.querySelector('.submit-label').classList.add('d-none');
            button.querySelector('.submit-loading').classList.remove('d-none');
        });
    });
</script>
@endpush
