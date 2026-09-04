@extends('layouts.app')

@section('title', $dashboard['title'] ?? 'Dashboard')

@section('content')
<div class="container-fluid py-4">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <p class="text-muted mb-1 text-uppercase small fw-semibold">{{ $role->name ?? 'Unassigned role' }}</p>
            <h2 class="fw-bold mb-2">{{ $dashboard['title'] }}</h2>
            <p class="text-muted mb-0">{{ $dashboard['subtitle'] }}</p>
        </div>
    </div>

    @if(empty($dashboard['widgets']))
        <div class="alert alert-warning border-0 shadow-sm">
            No dashboard widgets are assigned for this role yet.
        </div>
    @else
        <div class="row g-3">
            @foreach($dashboard['widgets'] as $widget)
                <div class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="text-muted small mb-2">{{ $widget }}</div>
                            <div class="fs-4 fw-semibold">—</div>
                            <p class="text-muted small mb-0 mt-2">Placeholder. This widget will be connected in a later module.</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
