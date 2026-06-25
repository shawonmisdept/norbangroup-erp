<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — {{ config('portal.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if($autoPrint ?? false)
        <script>
            window.addEventListener('load', () => {
                setTimeout(() => window.print(), 300);
            });
        </script>
    @endif
</head>
<body class="payslip-print-page antialiased">
    @yield('content')
</body>
</html>
