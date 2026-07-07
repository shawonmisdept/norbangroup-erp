<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f2744">
    <title>@yield('title', 'Careers') — {{ config('portal.name', config('app.name')) }}</title>
    @include('partials.erp.fonts-bengali')
    @hasSection('meta')
        @yield('meta')
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.careers.styles')
</head>
<body class="careers-page antialiased flex flex-col min-h-screen">
    @include('partials.portal.public-header', [
        'brandLabel' => trim($__env->yieldContent('portal-brand-label')) ?: 'Portal',
        'brandUrl'   => trim($__env->yieldContent('portal-brand-url')) ?: url('/'),
    ])

    <main class="careers-main flex-1 w-full @yield('portal-main-class')">
        @if(session('success'))
            <div class="careers-alert careers-alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="careers-alert careers-alert-error">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="careers-footer">
        <div class="portal-container">
        <p>
            &copy; {{ date('Y') }} {{ config('portal.name', config('app.name')) }}.
            — A Product of
            <a href="https://datastateltd.com/" target="_blank" rel="noopener noreferrer">
                Data State Ltd.
            </a>
        </p>
    </div>
</footer>
    @stack('scripts')
</body>
</html>
