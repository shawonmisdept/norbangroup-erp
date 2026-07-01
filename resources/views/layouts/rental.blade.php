<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#c2410c">
    @include('rental.partials.pwa-head')
    <title>@yield('title', 'Rental Driver') — {{ config('portal.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="rental-app text-gray-900">

    @auth('rental_driver')
        @php $driver = auth('rental_driver')->user()->rentalDriver; @endphp
        <div class="emp-shell">
            @hasSection('hero')
                @yield('hero')
            @elseif(! View::hasSection('no-header'))
                <div class="emp-hero emp-hero-sub">
                    <div class="mb-3">
                        @include('partials.portal.brand-logo', [
                            'size' => 'sm',
                            'variant' => 'rental',
                            'showName' => true,
                            'subtitle' => 'Rental Driver Portal',
                        ])
                    </div>
                    <div class="emp-hero-row">
                        <div class="min-w-0 flex-1">
                            @hasSection('back')
                                <a href="@yield('back')" class="emp-back">
                                    @include('employee.partials.tab-icon', ['icon' => 'chevron-left'])
                                    Back
                                </a>
                            @endif
                            <h1 class="truncate">@yield('page-title', 'Rental Driver')</h1>
                            @hasSection('page-subtitle')
                                <p class="emp-hero-subtitle truncate">@yield('page-subtitle')</p>
                            @else
                                <p class="emp-hero-subtitle truncate">{{ $driver?->name }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            @include('rental.partials.notification-bell')
                            <form method="POST" action="{{ route('rental.logout') }}">
                                @csrf
                                <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-white/80 backdrop-blur transition hover:bg-white/20" aria-label="Sign out">
                                    @include('employee.partials.tab-icon', ['icon' => 'logout'])
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <main class="emp-main">
                @include('rental.partials.pwa-install-banner')
                @if(session('success'))
                    <div class="emp-toast emp-toast-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="emp-toast emp-toast-error">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="emp-toast emp-toast-error space-y-1">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                @yield('content')
            </main>

            @include('rental.partials.bottom-nav')
        </div>
    @else
        @yield('content')
    @endauth

    @stack('scripts')
    @include('partials.confirm-dialog')
</body>
</html>
