<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set up password | Resource Reservation</title>
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logos/favicon.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ url('css/login.css') }}?v={{ @filemtime(public_path('css/login.css')) ?: time() }}">
    <link rel="stylesheet" href="{{ url('css/setup-password.css') }}?v={{ @filemtime(public_path('css/setup-password.css')) ?: time() }}">
</head>
<body class="setup-page">
    <canvas id="starfield" aria-hidden="true"></canvas>
    <main class="login-page login-shell">
        <section class="login-card setup-card" aria-labelledby="setup-title">
            <img src="{{ url('images/logos/nebula.png') }}" alt="SLT Mobitel Nebula Institute of Technology" class="login-logo">

            @if($expired)
                <h1 id="setup-title">Link expired</h1>
                <p class="login-subtitle">This password setup link is invalid or has expired. Setup links are valid for {{ $expiresMinutes }} minutes.</p>
                <p class="setup-note">Ask an administrator to send a new invitation, then try again.</p>
                <a class="login-btn setup-login-link" href="{{ route('login') }}">Back to sign in</a>
            @else
                <h1 id="setup-title">Set up your password</h1>
                <p class="login-subtitle">Choose a password to activate your Resource Reservation account. This link expires in {{ $expiresMinutes }} minutes.</p>

                @if($errors->any() && ! $errors->has('password') && ! $errors->has('password_confirmation'))
                    <div class="login-alert login-alert-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" novalidate>
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <label class="field-label" for="email">Email</label>
                    <div class="field field-readonly">
                        <i class="bi bi-envelope field-icon" aria-hidden="true"></i>
                        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" readonly tabindex="-1" autocomplete="username">
                    </div>

                    <label class="field-label" for="password">New password</label>
                    <div class="field {{ $errors->has('password') ? 'is-invalid' : '' }}">
                        <i class="bi bi-lock field-icon" aria-hidden="true"></i>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            autofocus
                            placeholder="At least 8 characters">
                        <button class="field-toggle" type="button" data-password-toggle="password" aria-label="Show password">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="field-error">{{ $message }}</p>
                    @else
                        <p class="field-hint">Use at least 8 characters.</p>
                    @enderror

                    <label class="field-label" for="password_confirmation">Re-enter password</label>
                    <div class="field {{ $errors->has('password_confirmation') ? 'is-invalid' : '' }}">
                        <i class="bi bi-shield-lock field-icon" aria-hidden="true"></i>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="Repeat your new password">
                        <button class="field-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Show password confirmation">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <p class="field-error">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="login-btn">Set password</button>
                </form>

                <a class="forgot-link" href="{{ route('login') }}">Back to sign in</a>
            @endif
        </section>
    </main>

    @php
        $nebulaStars = json_decode(@file_get_contents(public_path('js/nebula-stars.json')) ?: '{}');
    @endphp
    <script>
        window.NEBULA_STARS = @json($nebulaStars);
    </script>
    <script src="{{ url('js/login.js') }}?v={{ @filemtime(public_path('js/login.js')) ?: time() }}"></script>
</body>
</html>
