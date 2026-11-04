<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Nebula') | Nebula Institute of Technology</title>
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logos/favicon.jpg') }}">
    <link rel="stylesheet" href="{{ asset('css/icons/tabler-icons/tabler-icons.css') }}">
    <link href="{{ asset('css/styles.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/public-pages.css') }}?v=6" rel="stylesheet">
    @stack('styles')
</head>

<body class="pub-body">
    <header class="pub-brand">
        <div class="pub-brand-bar">
            <img src="{{ asset('images/logos/nebula.png') }}" alt="Nebula Institute of Technology" class="pub-logo">
            <span class="pub-brand-divider" aria-hidden="true"></span>
            <span class="pub-brand-tag"><i class="ti ti-building-community" aria-hidden="true"></i>Hostel Office</span>
        </div>
    </header>
    <main class="pub-main">
        @yield('content')
    </main>
    <footer class="pub-footer">
        © {{ now()->year }} Resource Reservation System · Nebula Institute of Technology
    </footer>
    @stack('scripts')
</body>

</html>
