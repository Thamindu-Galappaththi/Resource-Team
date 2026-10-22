@extends('layouts.app')

@section('title', 'Create Canteen Reservation')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/canteen-dashboard.css') }}?v={{ @filemtime(public_path('css/canteen-dashboard.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/canteen-pages.css') }}?v={{ @filemtime(public_path('css/canteen-pages.css')) ?: time() }}">
@endpush

@section('content')
<div class="container-fluid py-4 cp-page">
    <header class="cd-header">
        <div>
            <div class="cd-eyebrow"><i class="ti ti-tools-kitchen-2" aria-hidden="true"></i> Canteen operations</div>
            <h1 class="cd-title">Create reservation</h1>
            <p class="cd-subtitle">Plan your meal service with the canteen team.</p>
        </div>
        <div class="cd-actions">
            <a href="{{ route('canteen.index') }}" class="btn btn-outline-light"><i class="ti ti-arrow-left me-1" aria-hidden="true"></i>All reservations</a>
        </div>
    </header>

    @if($errors->any())
        <div class="alert alert-danger" role="alert"><strong>Please check the form.</strong> Some details need your attention.</div>
    @endif

    <section class="cp-surface">
        <div class="cp-panel-heading">
            <h2 class="h5 mb-1">Reservation request</h2>
            <p class="text-muted small mb-0">Requests remain pending until reviewed by the canteen team.</p>
        </div>
        <div class="cp-form-body">
            <form id="canteenReservationForm" method="POST" action="{{ route('canteen.store') }}">
                @include('canteen.reservations._form')
                <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 pt-4 mt-2 border-top cp-form-actions">
                    <a href="{{ route('canteen.index') }}" class="btn btn-outline-secondary">Discard</a>
                    <button id="canteenSubmitButton" type="submit" class="btn btn-primary">
                        <span class="submit-label"><i class="ti ti-check me-1" aria-hidden="true"></i>Submit reservation</span>
                        <span class="submit-loading d-none"><span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Submitting...</span>
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('canteenReservationForm');
        const button = document.getElementById('canteenSubmitButton');
        const date = document.getElementById('reservation_date');
        const orders = document.getElementById('number_of_orders');
        const meal = document.getElementById('meal_type');

        if (!form || !button || !date || !orders) return;

        const validateField = (field) => {
            field.classList.toggle('is-invalid', !field.validity.valid);
            field.classList.toggle('is-valid', field.validity.valid && field.value !== '');
        };

        form.querySelectorAll('input, select, textarea').forEach((field) => {
            field.addEventListener('input', () => validateField(field));
            field.addEventListener('change', () => validateField(field));
        });

        form.addEventListener('submit', (event) => {
            const mealWrap = meal?.closest('[data-cu-select]');
            if (mealWrap) {
                mealWrap.classList.toggle('is-invalid', !meal.value);
            }

            if (!meal?.value || date.value < date.min || Number(orders.value) < 1 || Number(orders.value) > 10000) {
                event.preventDefault();
                if (!meal?.value) {
                    mealWrap?.querySelector('.cu-dropdown-toggle')?.classList.add('is-invalid');
                    mealWrap?.querySelector('.cu-dropdown-toggle')?.focus();
                    return;
                }
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

@push('scripts')
<script src="{{ asset('js/resource-select.js') }}?v=2"></script>
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
<script src="{{ asset('js/canteen-ui.js') }}?v=2"></script>
@endpush
