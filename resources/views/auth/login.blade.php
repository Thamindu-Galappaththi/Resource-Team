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
    <link rel="stylesheet" href="{{ url('css/login.css') }}?v={{ @filemtime(public_path('css/login.css')) ?: time() }}">
    <style>
        html, body {
            margin: 0;
            min-height: 100%;
        }

        body {
            font-family: Inter, "Segoe UI", sans-serif;
            color: #111827;
            background: #12081c url("{{ url('images/backgrounds/nebula.jpg') }}") center / cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-page,
        .login-shell {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 18px;
            padding: 2.15rem 2rem 1.7rem;
            box-shadow: 0 22px 60px rgba(8, 16, 40, 0.38);
            position: relative;
        }

        .login-card::after {
            content: "";
            position: absolute;
            left: 22px;
            right: 22px;
            bottom: 0;
            height: 3px;
            border-radius: 3px 3px 0 0;
            background: #00aeef;
        }

        .login-logo {
            display: block;
            width: 220px;
            max-width: 72%;
            height: auto;
            margin: 0 auto 1.35rem;
        }

        .login-card h1 {
            margin: 0;
            text-align: center;
            font-size: 1.7rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #111827;
        }

        .login-subtitle {
            margin: 0.45rem 0 1.45rem;
            text-align: center;
            color: #9ca3af;
            font-size: 0.92rem;
        }

        .login-alert {
            border-radius: 10px;
            padding: 0.7rem 0.85rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .login-alert-error {
            color: #b91c1c;
            background: #fef2f2;
            border: 1px solid #fecaca;
        }

        .login-alert-success {
            color: #166534;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
        }

        .field-label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.86rem;
            font-weight: 700;
            color: #374151;
        }

        .field {
            position: relative;
            margin-bottom: 1.05rem;
        }

        .field-icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.05rem;
            pointer-events: none;
        }

        .field input {
            width: 100%;
            min-height: 48px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 0.65rem 2.6rem 0.65rem 2.45rem;
            font-size: 0.95rem;
            color: #111827;
            background: #fff;
            outline: none;
        }

        .field input::placeholder {
            color: #c4c9d4;
        }

        .field-toggle {
            position: absolute;
            right: 0.7rem;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #9ca3af;
            padding: 0.2rem;
            cursor: pointer;
        }

        .login-card .login-btn,
        button.login-btn {
            width: 100%;
            min-height: 48px;
            margin-top: 0.35rem;
            border: 0;
            border-radius: 10px;
            background: #0b3d91 !important;
            color: #fff !important;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
        }

        .forgot-link {
            display: block;
            margin-top: 1.2rem;
            text-align: center;
            color: #6b7280;
            text-decoration: none;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <main class="login-page login-shell">
        <section class="login-card" aria-labelledby="login-title">
            <img src="{{ url('images/logos/nebula.png') }}" alt="SLT Mobitel Nebula Institute of Technology" class="login-logo">

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

    <script src="{{ url('js/login.js') }}?v={{ @filemtime(public_path('js/login.js')) ?: time() }}"></script>
</body>
</html>
