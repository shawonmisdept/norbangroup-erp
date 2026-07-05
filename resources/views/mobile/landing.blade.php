<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#1e3a5f">
    @include('mobile.partials.pwa-head')
    <title>{{ config('portal.name') }} — Mobile App</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="mobile-app-bg antialiased">

    <div class="mobile-app-shell">
        <div class="mobile-app-brand">
            @include('partials.portal.brand-logo', [
                'size' => 'lg',
                'variant' => 'employee',
                'centered' => true,
                'showName' => true,
                'subtitle' => 'Employee & Driver Portal',
            ])
        </div>

        <p class="mobile-app-intro">Choose how you want to sign in</p>

        <div class="mobile-app-actions">
            <a href="{{ route('employee.login', ['source' => 'app']) }}"
               class="mobile-app-card mobile-app-card-employee"
               data-portal-shell-link="employee">
                <span class="mobile-app-card-icon" aria-hidden="true">👤</span>
                <span class="mobile-app-card-body">
                    <span class="mobile-app-card-title">Employee Portal</span>
                    <span class="mobile-app-card-text">Payslip, leave, attendance, transport</span>
                    <span class="mobile-app-card-hint">Sign in with Employee ID</span>
                </span>
                <span class="mobile-app-card-arrow" aria-hidden="true">→</span>
            </a>

            <a href="{{ route('rental.login', ['source' => 'app']) }}"
               class="mobile-app-card mobile-app-card-rental"
               data-portal-shell-link="rental">
                <span class="mobile-app-card-icon" aria-hidden="true">🚐</span>
                <span class="mobile-app-card-body">
                    <span class="mobile-app-card-title">Rental Driver</span>
                    <span class="mobile-app-card-text">Trips, odometer, trip alerts</span>
                    <span class="mobile-app-card-hint">Sign in with mobile number</span>
                </span>
                <span class="mobile-app-card-arrow" aria-hidden="true">→</span>
            </a>
        </div>

        <p class="mobile-app-footer">
            HR / Admin:
            <a href="{{ route('login') }}" class="mobile-app-footer-link">ERP sign in</a>
        </p>
    </div>

    <script>
        document.querySelectorAll('[data-portal-shell-link]').forEach(function (link) {
            link.addEventListener('click', function () {
                try {
                    sessionStorage.setItem('portal-shell', '1');
                } catch (e) {}
            });
        });
    </script>
</body>
</html>
