@extends('layouts.guest-login')

@section('title', 'Nebula Administration')

@section('content')
    <section class="login-card login-card--admin" aria-labelledby="login-title">
        <img src="{{ url('images/logos/nebula.png') }}" alt="SLT Mobitel Nebula Institute of Technology" class="login-logo">

        <a class="login-back" href="{{ route('login') }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            All sign-in options
        </a>

        <p class="login-badge">Nebula Administration</p>
        <h1 id="login-title">Resource Reservation</h1>
        <p class="login-subtitle">Sign in with your institute credentials</p>

        @if(session('status'))
            <div class="login-alert login-alert-success" role="alert">{{ session('status') }}</div>
        @endif

        <form id="loginForm" method="POST" action="{{ route('login.attempt') }}" novalidate>
            @csrf

            <label class="field-label" for="username">Username</label>
            <div class="field {{ $errors->has('username') ? 'is-invalid' : '' }}">
                <i class="bi bi-person field-icon" aria-hidden="true"></i>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="{{ old('username') }}"
                    required
                    autocomplete="username"
                    autofocus
                    placeholder="NIC or email"
                    aria-describedby="usernameError">
            </div>
            <p class="field-error" id="usernameError">{{ $errors->first('username') ?: 'Username is required.' }}</p>

            <label class="field-label" for="password">Password</label>
            <div class="field {{ $errors->has('password') ? 'is-invalid' : '' }}">
                <i class="bi bi-lock field-icon" aria-hidden="true"></i>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    aria-describedby="passwordError">
                <button class="field-toggle" type="button" id="togglePassword" aria-label="Show password">
                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                </button>
            </div>
            <p class="field-error" id="passwordError">{{ $errors->first('password') ?: 'Password is required.' }}</p>

            <button type="submit" class="login-btn">Sign In</button>
        </form>

        <a class="forgot-link" href="{{ route('password.request') }}">Forgot Password?</a>
    </section>
@endsection
