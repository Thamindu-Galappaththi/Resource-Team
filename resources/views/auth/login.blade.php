<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Resource Reservation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <main class="login-page">
        <section class="login-card" aria-labelledby="login-title">
            <img src="{{ asset('images/logos/nebula.png') }}" alt="SLT Mobitel Nebula Institute of Technology" class="login-logo">

            <h1 id="login-title">Resource Reservation</h1>
            <p class="login-subtitle">Sign in to manage your institutional assets</p>

            @if($errors->any())
                <div class="login-alert login-alert-error" role="alert">{{ $errors->first() }}</div>
            @endif

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
                        placeholder="Enter your username">
                </div>

                <label class="field-label" for="password">Password</label>
                <div class="field {{ $errors->has('password') ? 'is-invalid' : '' }}">
                    <i class="bi bi-lock field-icon" aria-hidden="true"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••">
                    <button class="field-toggle" type="button" id="togglePassword" aria-label="Show password">
                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>

                <button type="submit" class="login-btn">Sign In</button>
            </form>

            <a class="forgot-link" href="{{ route('password.request') }}">Forgot Password?</a>
        </section>
    </main>

    <script src="{{ asset('js/login.js') }}"></script>
</body>
</html>
