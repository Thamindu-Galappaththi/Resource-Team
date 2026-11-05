@extends('layouts.public')

@section('title', 'Link Not Valid')

@section('content')
<div class="pub-card">
    <div class="pub-done">
        <i class="ti ti-alert-circle pub-invalid-icon" aria-hidden="true"></i>
        <h1>This link is not valid</h1>
        <p class="mb-2">The hostel request link may be incomplete or typed incorrectly.</p>
        <p class="text-muted mb-4">Open the link again from the message the hostel manager sent you, without changing it. If it still does not work, call the logistics desk at <a href="tel:+94112954069" class="pub-phone">011 2954 069</a>.</p>
    </div>
</div>
@endsection
