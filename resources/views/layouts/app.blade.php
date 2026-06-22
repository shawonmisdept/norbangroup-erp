<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen flex flex-col">

    @php
        $isAdmin = request()->routeIs('admin.*');
        $isPublic = request()->routeIs('orders.create', 'orders.success');
        $containerClass = $isAdmin ? 'w-full px-4 sm:px-6 lg:px-8' : 'max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8';
    @endphp

    @if($isPublic)
    <header class="fixed top-0 left-0 right-0 z-50">
        <div class="{{ $containerClass }}">
            <div class="flex justify-end py-4">
                @auth
                    @if(auth()->user()->hasPermission('orders.view'))
                        <a href="{{ route('admin.requirements.index') }}"
                           class="inline-flex items-center bg-gold hover:bg-gold-dark text-white text-sm font-semibold px-5 py-2 rounded-lg shadow-sm transition">
                            Dashboard
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center bg-gold hover:bg-gold-dark text-white text-sm font-semibold px-5 py-2 rounded-lg shadow-sm transition">
                        Team Sign-In
                    </a>
                @endauth
            </div>
        </div>
    </header>
    @else
    <nav class="bg-brand sticky top-0 z-50">
        <div class="{{ $containerClass }}">
            <div class="flex items-center justify-between h-16">

                <a href="{{ route('orders.create') }}" class="flex items-center gap-3">
                    @if(config('portal.frontend_logo'))
                        <img src="{{ config('portal.frontend_logo') }}" alt="{{ config('portal.name') }}"
                             class="h-9 w-auto max-w-[140px] object-contain">
                    @else
                        <div class="w-9 h-9 bg-gold rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    @endif
                    <div>
                        <span class="text-white font-semibold text-sm">{{ config('portal.name') }}</span>
                        <span class="block text-xs text-white/40 tracking-widest uppercase">{{ config('portal.tagline') }}</span>
                    </div>
                </a>

                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('orders.create') }}"
                       class="px-4 py-2 text-sm rounded-sm text-white/70 hover:text-white hover:bg-white/10 transition
                              {{ request()->routeIs('orders.create') ? 'bg-white/10 text-white' : '' }}">
                        Requirement Form
                    </a>
                    @auth
                        @if(auth()->user()->hasPermission('orders.view'))
                            <a href="{{ route('admin.requirements.index') }}"
                               class="px-4 py-2 text-sm rounded-sm text-white/70 hover:text-white hover:bg-white/10 transition">
                                ERP Dashboard
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 text-sm rounded-sm text-white/70 hover:text-white hover:bg-white/10 transition">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                           class="px-4 py-2 text-sm rounded-sm text-white/70 hover:text-white hover:bg-white/10 transition
                                  {{ request()->routeIs('login') ? 'bg-white/10 text-white' : '' }}">
                            Team Sign-In
                        </a>
                    @endauth
                </div>

            </div>
        </div>
    </nav>
    @endif

    @include('partials.erp.toast')

    <main class="flex-1 w-full">
        @yield('content')
    </main>

    @unless($isPublic)
    <footer class="border-t border-gray-200 bg-white">
        <div class="{{ $containerClass }} py-4 text-center text-xs text-gray-400">
            &copy; 1990 - {{ date('Y') }} Norban Group — A Product of Data State Ltd
        </div>
    </footer>
    @endunless

</body>
</html>
