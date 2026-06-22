<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex antialiased">

    {{-- Brand panel --}}
    <div class="hidden lg:flex lg:w-[420px] xl:w-[480px] bg-erp-sidebar text-white flex-col justify-between p-10 shrink-0">
        <div>
            <div class="flex items-center gap-3 mb-10">
                @if(config('portal.navbar_logo'))
                    <img src="{{ config('portal.navbar_logo') }}" alt="{{ config('portal.name') }}"
                         class="h-10 w-auto max-w-[220px] object-contain">
                @else
                    <div class="w-10 h-10 bg-gold rounded-sm flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M4 7h16M4 12h16M4 17h10" stroke-linecap="round"/>
                        </svg>
                    </div>
                @endif
            </div>
            <h1 class="text-3xl font-bold leading-snug mb-3">Garment Operations<br>Management System</h1>
            <p class="text-sm text-white/60 leading-relaxed">
                Manage requirements, production references and team access from a unified PLM workspace.
            </p>
        </div>
        <p class="text-[11px] text-white/30">&copy; 1990 - {{ date('Y') }} Norban Group of Companies. A Product of Data State Ltd.</p>
    </div>

    {{-- Login form --}}
    <div class="flex-1 flex items-center justify-center bg-erp-bg p-6">
        <div class="w-full max-w-sm">
            <div class="lg:hidden flex items-center gap-2 mb-8">
                @if(config('portal.frontend_logo') ?: config('portal.navbar_logo'))
                    <img src="{{ config('portal.frontend_logo') ?: config('portal.navbar_logo') }}" alt="{{ config('portal.name') }}"
                         class="h-8 w-auto max-w-[120px] object-contain">
                @else
                    <div class="w-8 h-8 bg-brand rounded-sm flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M4 7h16M4 12h16M4 17h10" stroke-linecap="round"/>
                        </svg>
                    </div>
                @endif
                <span class="font-bold text-gray-800">{{ config('portal.name') }} ERP</span>
            </div>

            <div class="erp-panel p-6">
                <h2 class="text-base font-bold text-gray-900">Sign in</h2>
                <p class="text-xs text-gray-500 mt-1 mb-5">Enter your credentials to access {{ config('portal.name') }}</p>

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-sm p-3 text-xs text-red-700 mb-4">
                        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="erp-form-label">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus class="erp-input !text-xs">
                    </div>
                    <div>
                        <label class="erp-form-label">Password</label>
                        <input type="password" name="password" required class="erp-input !text-xs">
                    </div>
                    <label class="flex items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-brand focus:ring-brand">
                        Remember this device
                    </label>
                    <button type="submit" class="erp-btn-primary w-full justify-center !py-2.5 !text-sm">
                        Sign In
                    </button>
                </form>
            </div>

            <p class="text-center text-[11px] text-gray-400 mt-4">
                <a href="{{ route('orders.create') }}" class="hover:text-brand">← Back to public requirement form</a>
            </p>
        </div>
    </div>
</body>
</html>
