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

        <a class="login-choice login-choice--google" href="{{ route('login.google') }}">
            <span class="login-choice-kicker">Google & Gmail</span>
            <span class="login-choice-title">Sign in with Google</span>
            <span class="login-choice-hint">Use your Google or Gmail account</span>
            <span class="google-login-btn">
                <svg class="google-logo" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.8-2.4 3.66v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.15z"/>
                    <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.26v3.15C3.25 21.37 7.33 24 12 24z"/>
                    <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.26C.46 8.16 0 9.98 0 12c0 2.02.46 3.84 1.26 5.42l4.02-3.15z"/>
                    <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.25 2.63 1.26 6.58l4.02 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                </svg>
                Login with Google
            </span>
        </a>

        <a class="login-choice login-choice--facebook" href="{{ route('login.facebook') }}">
            <span class="login-choice-kicker">Facebook</span>
            <span class="login-choice-title">Sign in with Facebook</span>
            <span class="login-choice-hint">Use your personal Facebook account</span>
            <span class="facebook-login-btn">
                <svg class="fb-logo" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                Login with Facebook
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
