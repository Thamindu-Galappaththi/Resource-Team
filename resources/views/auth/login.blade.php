@extends('layouts.guest-login')

@section('title', 'Sign in')

@section('content')
    <section class="login-card login-card--gateway" aria-labelledby="login-title">
        <img src="{{ url('images/logos/nebula.png') }}" alt="SLT Mobitel Nebula Institute of Technology" class="login-logo">

        <h1 id="login-title">Resource Reservation</h1>
        <p class="login-subtitle">Choose how you sign in</p>

        @if(session('status'))
            <div class="login-alert login-alert-success" role="alert">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="login-alert login-alert-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <a class="login-choice login-choice--microsoft" href="{{ route('login.microsoft') }}">
            <span class="login-choice-kicker">SLT employee staff</span>
            <span class="login-choice-title">Sign in with Microsoft</span>
            <span class="login-choice-hint">Use your SLT Microsoft 365 work account</span>
            <span class="ms-login-btn">
                <svg class="ms-logo" viewBox="0 0 21 21" aria-hidden="true" focusable="false">
                    <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                    <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                    <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                    <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                </svg>
                Login with Microsoft
            </span>
        </a>

        <div class="login-or" role="separator"><span>or</span></div>

        <a class="login-choice login-choice--admin" href="{{ route('login.admin') }}">
            <span class="login-choice-kicker">Institute operations</span>
            <span class="login-choice-title">Nebula Administration</span>
            <span class="login-choice-hint">Username and password for administrators</span>
            <span class="admin-login-btn">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                Nebula Administration
            </span>
        </a>
    </section>
@endsection
