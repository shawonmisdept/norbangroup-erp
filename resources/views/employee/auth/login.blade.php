<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#1e3a5f">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Sign In — {{ config('portal.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="emp-login-bg antialiased">

    <div class="w-full max-w-sm">
        {{-- App branding --}}
        <div class="mb-8 text-center">
            @if(config('portal.frontend_logo') ?: config('portal.navbar_logo'))
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 p-3 backdrop-blur">
                    <img src="{{ config('portal.frontend_logo') ?: config('portal.navbar_logo') }}" alt="{{ config('portal.name') }}"
                         class="h-full w-full object-contain brightness-0 invert">
                </div>
            @else
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 text-2xl font-bold text-white backdrop-blur">
                    {{ strtoupper(substr(config('portal.name'), 0, 1)) }}
                </div>
            @endif
            <h1 class="text-xl font-bold text-white">{{ config('portal.name') }}</h1>
            <p class="mt-1 text-sm text-white/50">Employee Self-Service</p>
        </div>

        <div class="emp-login-card">
            <div class="bg-gradient-to-r from-brand to-brand-light px-6 py-5 text-white">
                <h2 class="text-lg font-bold">Welcome back</h2>
                <p class="mt-0.5 text-xs text-white/60">Sign in with your Employee ID</p>
            </div>

            <div class="p-6">
                @if($errors->any())
                    <div class="emp-toast emp-toast-error mb-4">
                        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('employee.login.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="emp-label">Employee ID</label>
                        <input type="text" name="employee_code" value="{{ old('employee_code') }}" required autofocus
                               placeholder="e.g. NCL-D001" class="emp-input font-mono">
                    </div>
                    <div>
                        <label class="emp-label">Password</label>
                        <input type="password" name="password" required placeholder="••••••••" class="emp-input">
                    </div>
                    <label class="flex items-center gap-2.5 text-xs text-gray-500">
                        <input type="checkbox" name="remember" class="rounded-md border-gray-300 text-brand focus:ring-brand">
                        Remember this device
                    </label>
                    <button type="submit" class="emp-btn w-full">
                        Sign In
                    </button>
                </form>
            </div>
        </div>

        <p class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-xs font-medium text-white/40 transition hover:text-white/70">HR / Admin sign in →</a>
        </p>
    </div>

</body>
</html>
