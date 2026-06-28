<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#1e3a5f">
    @include('employee.partials.pwa-head')
    <title>@yield('title', 'Employee Portal') — {{ config('portal.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="emp-app text-gray-900">

    @auth('employee')
        @php $employee = auth('employee')->user()->employee; @endphp
        <div class="emp-shell">
            @hasSection('hero')
                @yield('hero')
            @elseif(! View::hasSection('no-header'))
                <div class="emp-hero emp-hero-sub">
                    <div class="emp-hero-row">
                        <div class="min-w-0 flex-1">
                            @hasSection('back')
                                <a href="@yield('back')" class="emp-back">
                                    @include('employee.partials.tab-icon', ['icon' => 'chevron-left'])
                                    Back
                                </a>
                            @endif
                            <h1 class="truncate">@yield('page-title', 'Employee Portal')</h1>
                            @hasSection('page-subtitle')
                                <p class="emp-hero-subtitle truncate">@yield('page-subtitle')</p>
                            @endif
                        </div>
                        @hasSection('header-action')
                            <div class="shrink-0 pt-1">@yield('header-action')</div>
                        @else
                            <div class="shrink-0 pt-1">@include('employee.partials.notification-bell')</div>
                        @endif
                    </div>
                </div>
            @endif

            <main class="emp-main">
                @include('employee.partials.pwa-install-banner')
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

            @include('employee.partials.bottom-nav')
        </div>
    @else
        @yield('content')
    @endauth

    @stack('scripts')
    @include('partials.confirm-dialog')
</body>
</html>
