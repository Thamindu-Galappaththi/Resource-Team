@extends('layouts.app')

@section('title', 'Page not available')

@push('styles')
<style>
.app-error {
    display: grid;
    place-items: center;
    min-height: calc(100vh - 180px);
    padding: 2rem 0 3rem;
}
.app-error__card {
    width: min(100%, 32rem);
    padding: 2rem 1.75rem 1.75rem;
    border: 1px solid #dbe4f0;
    border-radius: 1.25rem;
    background: #fff;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.1);
    text-align: center;
}
.app-error__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3.5rem;
    height: 3.5rem;
    margin-bottom: 1rem;
    border-radius: 999px;
    background: #eef4ff;
    color: #0b3d91;
    font-size: 1.7rem;
}
.app-error h1 {
    margin: 0 0 0.5rem;
    color: #14315e;
    font-size: 1.45rem;
    font-weight: 750;
}
.app-error p {
    margin: 0 0 1.35rem;
    color: #5b6b82;
    line-height: 1.5;
}
.app-error__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.65rem;
}
.app-error .btn {
    min-height: 42px;
    border-radius: 0.7rem;
    font-weight: 650;
}
</style>
@endpush

@section('content')
@php
    $isSltEmployee = auth()->user()?->hasRole('slt_employee');
    $home = $isSltEmployee
        ? (request()->routeIs('hostel.*') ? route('hostel.home')
            : (request()->routeIs('canteen.*') ? route('canteen.home') : route('dashboard')))
        : route('dashboard');
@endphp
<div class="app-error">
    <div class="app-error__card">
        <span class="app-error__icon" aria-hidden="true"><i class="ti ti-lock"></i></span>
        <h1>Page not available</h1>
        @if($isSltEmployee)
            <p>This section is not part of your workspace. Use Calendar, Create, or My requests to continue.</p>
        @else
            <p>You do not have access to this page. Return to the dashboard to keep working.</p>
        @endif
        <div class="app-error__actions">
            <a class="btn btn-primary" href="{{ $home }}">Back to dashboard</a>
        </div>
    </div>
</div>
@endsection
