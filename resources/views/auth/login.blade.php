<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#152d4a">
    <title>Sign In — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-login-page antialiased">

    <div class="admin-login-shell">
        <aside class="admin-login-brand">
            <div class="admin-login-brand-glow" aria-hidden="true"></div>
            <div class="admin-login-brand-inner">
                <div class="admin-login-logo">
                    @if(config('portal.frontend_logo') ?: config('portal.navbar_logo'))
                        <img src="{{ config('portal.frontend_logo') ?: config('portal.navbar_logo') }}"
                             alt="{{ config('portal.name') }}"
                             class="h-11 w-auto max-w-[200px] object-contain brightness-0 invert">
                    @else
                        <div class="admin-login-logo-fallback">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M4 7h16M4 12h16M4 17h10" stroke-linecap="round"/>
                            </svg>
                        </div>
                    @endif
                </div>

                <div class="admin-login-hero">
                    <p class="admin-login-eyebrow">{{ config('portal.name') }} · {{ config('portal.tagline') }}</p>
                    <h1 class="admin-login-title">Garment Operations<br>Management System</h1>
                    <p class="admin-login-tagline">
                        Unified PLM workspace for orders, production, HRM and team access — all in one secure portal.
                    </p>
                </div>

                <ul class="admin-login-highlights">
                    <li>
                        <span class="admin-login-highlight-icon">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        Role-based secure access
                    </li>
                    <li>
                        <span class="admin-login-highlight-icon">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        Real-time order &amp; KPI tracking
                    </li>
                    <li>
                        <span class="admin-login-highlight-icon">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        HRM, payroll &amp; compliance
                    </li>
                </ul>

                <p class="admin-login-footer">
                    &copy; 1990 – {{ date('Y') }} Norban Group ·
                    <a href="https://datastateltd.com/" target="_blank" rel="noopener noreferrer">
                        Data State Ltd.
                    </a>
                </p>
            </div>
        </aside>

        <main class="admin-login-main">
            <div class="admin-login-form-wrap">
                <div class="admin-login-mobile-head lg:hidden">
                    @if(config('portal.frontend_logo') ?: config('portal.navbar_logo'))
                        <img src="{{ config('portal.frontend_logo') ?: config('portal.navbar_logo') }}"
                             alt="{{ config('portal.name') }}"
                             class="h-9 w-auto max-w-[140px] object-contain">
                    @else
                        <div class="admin-login-logo-fallback admin-login-logo-fallback-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M4 7h16M4 12h16M4 17h10" stroke-linecap="round"/>
                            </svg>
                        </div>
                    @endif
                    <div>
                        <p class="text-sm font-bold text-gray-900">Norban Group</p>
                        <p class="text-[11px] text-gray-500">Team Sign-In</p>
                    </div>
                </div>

                <div class="admin-login-card">
                    <div class="admin-login-card-header">
                        <h2 class="text-lg font-bold">Welcome back</h2>
                        <p class="mt-0.5 text-xs text-white/70">Sign in to {{ config('portal.name') }} ERP</p>
                    </div>

                    <div class="admin-login-card-body">
                        @if($errors->any())
                            <div class="admin-login-error">
                                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="admin-login-label" for="email">Email Address</label>
                                <div class="admin-login-input-wrap">
                                    <svg class="admin-login-input-icon" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                        <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                                           placeholder="you@company.com" class="admin-login-input">
                                </div>
                            </div>
                            <div>
                                <label class="admin-login-label" for="password">Password</label>
                                <div class="admin-login-input-wrap">
                                    <svg class="admin-login-input-icon" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <input type="password" id="password" name="password" required
                                           placeholder="••••••••" class="admin-login-input">
                                </div>
                            </div>
                            <label class="flex items-center gap-2.5 text-xs text-gray-500">
                                <input type="checkbox" name="remember" class="rounded-md border-gray-300 text-brand focus:ring-brand">
                                Remember this device
                            </label>
                            <button type="submit" class="admin-login-submit">
                                Sign In
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="admin-login-portals">
                    <a href="{{ route('employee.login') }}" class="admin-login-portal">
                        <span class="admin-login-portal-icon admin-login-portal-icon-emp">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span>
                            <span class="admin-login-portal-title">Employee Portal</span>
                            <span class="admin-login-portal-sub">Self-service sign in</span>
                        </span>
                    </a>
                    <a href="{{ route('rental.login') }}" class="admin-login-portal admin-login-portal-rental">
                        <span class="admin-login-portal-icon admin-login-portal-icon-rental">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span>
                            <span class="admin-login-portal-title">Rental Driver Portal</span>
                            <span class="admin-login-portal-sub">Driver sign in</span>
                        </span>
                    </a>
                </div>
            </div>
        </main>
    </div>

</body>
</html>
