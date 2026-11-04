@extends('layouts.public')

@section('title', 'Request Submitted')

@section('content')
<div class="pub-card">
    <div class="pub-done">
        <i class="ti ti-circle-check" aria-hidden="true"></i>
        <h1>Request submitted</h1>
        <p class="mb-0">Thank you. Your hostel request was sent to the hostel manager.</p>
        <div class="pub-ref">{{ $reference }}</div>
        <p class="text-muted mb-4">Keep this reference. You will receive an SMS when the hostel manager approves, rejects, or cancels your request.</p>
        <a href="{{ route('hostel.student-form', $token) }}" class="btn btn-outline-primary">Submit another request</a>
    </div>
</div>
@endsection
