<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Login') | Resource Reservation</title>
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logos/favicon.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ url('css/login.css') }}?v={{ @filemtime(public_path('css/login.css')) ?: time() }}">
    <link rel="stylesheet" href="{{ url('css/action-lock.css') }}?v=1">
    @yield('head')
</head>

<body>
    <canvas id="starfield" aria-hidden="true"></canvas>
    <main class="login-page login-shell">
        @yield('content')
    </main>
    <footer class="login-footer">
        <div class="login-footer-accent" aria-hidden="true"></div>
        <p class="login-footer-brand">SLT Mobitel · Nebula Institute of Technology</p>
        <p class="login-footer-meta">© {{ now()->year }} Resource Reservation System. All rights reserved.</p>
    </footer>

    @php
    $nebulaStars = json_decode(@file_get_contents(public_path('js/nebula-stars.json')) ?: '{}');
    @endphp
    <script>
    window.NEBULA_STARS = @json($nebulaStars);
    </script>
    <script src="{{ url('js/action-lock.js') }}?v=2"></script>
    <script src="{{ url('js/login.js') }}?v={{ @filemtime(public_path('js/login.js')) ?: time() }}"></script>
    @yield('scripts')
</body>

</html>