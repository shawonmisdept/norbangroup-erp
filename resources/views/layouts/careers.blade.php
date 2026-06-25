<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f2744">
    <title>@yield('title', 'Careers') — {{ config('portal.name', config('app.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.careers.styles')
</head>
<body class="careers-page antialiased flex flex-col min-h-screen">
    <header class="careers-header">
        <div class="careers-header-accent" aria-hidden="true"></div>
        <div class="careers-header-inner">
            <a href="{{ route('careers.index') }}" class="careers-brand">
                <span class="careers-brand-logo">
                    @if($logo = config('portal.frontend_logo') ?: config('portal.navbar_logo'))
                        <img src="{{ $logo }}" alt="Logo" class="careers-brand-logo-img">
                    @else
                        <span class="careers-logo-mark">N</span>
                    @endif
                </span>
                <span class="careers-brand-divider" aria-hidden="true"></span>
                <span class="careers-brand-label">Career</span>
            </a>
            <nav class="careers-nav">
                <a href="{{ route('careers.index') }}" class="{{ request()->routeIs('careers.index', 'careers.show', 'careers.apply') ? 'active' : '' }}">
                    <svg class="careers-nav-icon" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Open Jobs
                </a>
                <a href="{{ route('careers.track') }}" class="{{ request()->routeIs('careers.track*') ? 'active' : '' }}">
                    <svg class="careers-nav-icon" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Track Application
                </a>
            </nav>
        </div>
    </header>

    <main class="careers-main flex-1 w-full">
        @if(session('success'))
            <div class="careers-alert careers-alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="careers-alert careers-alert-error">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="careers-footer">
        <p>&copy; {{ date('Y') }} {{ config('portal.name', config('app.name')) }}. Building careers in garments manufacturing.</p>
    </footer>
    @stack('scripts')
</body>
</html>
