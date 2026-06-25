<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen flex flex-col" x-data="{ mobileNavOpen: false }">

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
    <nav class="bg-brand sticky top-0 z-50 safe-top">
        <div class="{{ $containerClass }}">
            <div class="flex items-center justify-between h-14 sm:h-16">

                <a href="{{ route('orders.create') }}" class="flex items-center gap-2 sm:gap-3 min-w-0">
                    @if(config('portal.frontend_logo'))
                        <img src="{{ config('portal.frontend_logo') }}" alt="{{ config('portal.name') }}"
                             class="h-8 sm:h-9 w-auto max-w-[120px] sm:max-w-[140px] object-contain shrink-0">
                    @else
                        <div class="w-8 h-8 sm:w-9 sm:h-9 bg-gold rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <span class="text-white font-semibold text-sm truncate block">{{ config('portal.name') }}</span>
                        <span class="hidden sm:block text-xs text-white/40 tracking-widest uppercase">{{ config('portal.tagline') }}</span>
                    </div>
                </a>

                <button type="button" @click="mobileNavOpen = !mobileNavOpen"
                        class="md:hidden p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-sm min-w-[2.5rem] min-h-[2.5rem] flex items-center justify-center"
                        aria-label="Toggle menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path x-show="!mobileNavOpen" d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/>
                        <path x-show="mobileNavOpen" x-cloak d="M6 18L18 6M6 6l12 12" stroke-linecap="round"/>
                    </svg>
                </button>

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

            {{-- Mobile nav --}}
            <div x-show="mobileNavOpen" x-transition x-cloak
                 class="md:hidden border-t border-white/10 py-2 space-y-0.5">
                <a href="{{ route('orders.create') }}" @click="mobileNavOpen = false"
                   class="block px-3 py-2.5 text-sm rounded-sm text-white/80 hover:text-white hover:bg-white/10 {{ request()->routeIs('orders.create') ? 'bg-white/10 text-white' : '' }}">
                    Requirement Form
                </a>
                @auth
                    @if(auth()->user()->hasPermission('orders.view'))
                        <a href="{{ route('admin.requirements.index') }}" @click="mobileNavOpen = false"
                           class="block px-3 py-2.5 text-sm rounded-sm text-white/80 hover:text-white hover:bg-white/10">
                            ERP Dashboard
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full text-left px-3 py-2.5 text-sm rounded-sm text-white/80 hover:text-white hover:bg-white/10">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" @click="mobileNavOpen = false"
                       class="block px-3 py-2.5 text-sm rounded-sm text-white/80 hover:text-white hover:bg-white/10 {{ request()->routeIs('login') ? 'bg-white/10 text-white' : '' }}">
                        Team Sign-In
                    </a>
                @endauth
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

    <style>[x-cloak]{display:none!important}</style>
</body>
</html>
