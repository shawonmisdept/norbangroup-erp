<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#c2410c">
    <title>Sign In — Rental Driver</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="rental-login-bg antialiased">

    <div class="w-full max-w-sm mx-auto min-h-screen flex flex-col justify-center p-4">
        <div class="mb-8">
            @include('partials.portal.brand-logo', [
                'size' => 'lg',
                'variant' => 'rental',
                'centered' => true,
                'showName' => true,
                'subtitle' => 'Rental Driver Portal',
            ])
        </div>

        <div class="rental-login-card">
            <div class="rental-login-header px-6 py-5 text-white">
                <h2 class="text-lg font-bold">Welcome, Driver</h2>
                <p class="mt-0.5 text-xs text-white/70">Sign in with your mobile number</p>
            </div>

            <div class="p-6">
                @if($errors->any())
                    <div class="emp-toast emp-toast-error mb-4">
                        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('rental.login.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="emp-label">Mobile</label>
                        <input type="text" name="mobile" value="{{ old('mobile') }}" required autofocus
                               placeholder="e.g. 01710000001" class="emp-input font-mono">
                    </div>
                    <div>
                        <label class="emp-label">Password</label>
                        <input type="password" name="password" required placeholder="••••••••" class="emp-input">
                    </div>
                    <label class="flex items-center gap-2.5 text-xs text-gray-500">
                        <input type="checkbox" name="remember" class="rounded-md border-gray-300 text-orange-600 focus:ring-orange-500">
                        Remember this device
                    </label>
                    <button type="submit" class="rental-btn w-full">Sign In</button>
                </form>
            </div>
        </div>

        <p class="mt-6 text-center">
            <a href="{{ route('employee.login') }}" class="text-xs font-medium text-orange-200/40 transition hover:text-orange-100/80">Employee portal →</a>
        </p>
    </div>

</body>
</html>
