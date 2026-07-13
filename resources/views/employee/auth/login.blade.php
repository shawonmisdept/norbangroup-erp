<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#1e3a5f">
    @include('employee.partials.pwa-head')
    @if(request('source') === 'app')
        <meta name="portal-shell" content="app">
        <script>try{sessionStorage.setItem('portal-shell','1');}catch(e){}</script>
    @endif
    <title>Sign In — {{ config('portal.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="emp-login-bg antialiased">

    <div class="w-full max-w-sm">
        {{-- App branding --}}
        <div class="mb-8">
            @include('partials.portal.brand-logo', [
                'size' => 'lg',
                'variant' => 'employee',
                'centered' => true,
                'showName' => true,
                'subtitle' => 'Employee Self-Service',
            ])
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
                               placeholder="e.g. 3030" class="emp-input font-mono">
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

        <p class="mt-6 text-center space-y-2">
            @if(request('source') === 'app')
                <a href="{{ route('mobile.landing') }}" class="mobile-app-switch-link block">← Switch portal</a>
            @else
                <a href="{{ route('login') }}" class="text-xs font-medium text-white/40 transition hover:text-white/70">HR / Admin sign in →</a>
            @endif
        </p>
    </div>

</body>
</html>
