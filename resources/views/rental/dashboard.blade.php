@extends('layouts.rental')

@section('title', 'Home')

@section('hero')
<div class="emp-hero">
    <div class="emp-hero-inner relative flex items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-white/15 text-lg font-bold text-white ring-2 ring-white/20">
                {{ strtoupper(substr($driver->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                @php
                    $hour = (int) now()->format('G');
                    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
                @endphp
                <p class="text-xs font-medium text-white/60">{{ $greeting }}</p>
                <h1 class="truncate text-lg font-bold">{{ $driver->name }}</h1>
                <p class="truncate text-[11px] text-white/50">Rental Driver · {{ $driver->factory?->name ?? '—' }}</p>
            </div>
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

    @if($driver->defaultVehicle)
        <div class="relative mt-5 overflow-hidden rounded-2xl bg-white/10 p-4 backdrop-blur">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-white/50">Default Vehicle</p>
            <p class="mt-1 text-base font-bold">{{ $driver->defaultVehicle->displayLabel() }}</p>
            @if($driver->rentalVendor)
                <p class="mt-0.5 text-xs text-white/60">{{ $driver->vendorLabel() }}</p>
            @endif
        </div>
    @endif
</div>
@endsection

@section('content')
<div class="space-y-5">

    @if($unreadNotifications > 0)
        <a href="{{ route('rental.notifications.index') }}" class="emp-card flex items-center gap-3 p-4 active:bg-gray-50">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 text-sm font-bold">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-900">New notification{{ $unreadNotifications > 1 ? 's' : '' }}</p>
                <p class="text-xs text-gray-500">Tap to view trip alerts</p>
            </div>
            <span class="emp-btn-sm-secondary">View</span>
        </a>
    @endif

    @if($activeTrips > 0)
        <a href="{{ route('rental.trips') }}" class="emp-card flex items-center gap-3 p-4 border-amber-200 bg-amber-50 active:bg-amber-100/80">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-200 text-amber-800 text-sm font-bold">{{ $activeTrips }}</span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-amber-900">Active trip{{ $activeTrips > 1 ? 's' : '' }} waiting</p>
                <p class="text-xs text-amber-700">Start or complete assigned trips</p>
            </div>
            <span class="emp-btn-sm">Open</span>
        </a>
    @endif

    <div>
        <p class="emp-section-title">My Profile</p>
        <div class="emp-card overflow-hidden divide-y divide-gray-100">
            <div class="flex items-start justify-between gap-3 px-4 py-3">
                <span class="text-xs text-gray-500 shrink-0">Name</span>
                <span class="text-sm font-medium text-gray-900 text-right">{{ $driver->name }}</span>
            </div>
            <div class="flex items-start justify-between gap-3 px-4 py-3">
                <span class="text-xs text-gray-500 shrink-0">Mobile</span>
                <span class="text-sm font-medium text-gray-900 text-right tabular-nums">{{ $driver->mobile ?? '—' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3 px-4 py-3">
                <span class="text-xs text-gray-500 shrink-0">NID Number</span>
                <span class="text-sm font-medium text-gray-900 text-right tabular-nums">{{ $driver->nid_number ?? '—' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3 px-4 py-3">
                <span class="text-xs text-gray-500 shrink-0">License Number</span>
                <span class="text-sm font-medium text-gray-900 text-right tabular-nums">{{ $driver->license_number ?? '—' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3 px-4 py-3">
                <span class="text-xs text-gray-500 shrink-0">Vendor / Company</span>
                <span class="text-sm font-medium text-gray-900 text-right">{{ $driver->vendorLabel() }}</span>
            </div>
        </div>
    </div>

    <div>
        <p class="emp-section-title">Quick Actions</p>
        <div class="grid grid-cols-4 gap-2.5">
            <a href="{{ route('rental.dashboard') }}" class="emp-quick">
                <span class="emp-quick-icon bg-indigo-50 text-indigo-600">
                    @include('employee.partials.tab-icon', ['icon' => 'home'])
                </span>
                <span class="text-[10px] font-semibold text-gray-700">Home</span>
            </a>
            <a href="{{ route('rental.trips') }}" class="emp-quick">
                <span class="emp-quick-icon bg-sky-50 text-sky-600">
                    @include('rental.partials.tab-icon', ['icon' => 'car'])
                </span>
                <span class="text-[10px] font-semibold text-gray-700">Trips</span>
            </a>
            <a href="{{ route('rental.odometer') }}" class="emp-quick">
                <span class="emp-quick-icon bg-violet-50 text-violet-600">
                    @include('rental.partials.tab-icon', ['icon' => 'gauge'])
                </span>
                <span class="text-[10px] font-semibold text-gray-700">Daily KM</span>
            </a>
            <a href="{{ route('rental.notifications.index') }}" class="emp-quick">
                <span class="emp-quick-icon bg-emerald-50 text-emerald-600 relative">
                    @include('rental.partials.tab-icon', ['icon' => 'bell'])
                    @if($unreadNotifications > 0)
                        <span class="absolute -top-1 -right-1 min-w-[14px] h-3.5 px-0.5 rounded-full bg-gold text-white text-[8px] font-bold flex items-center justify-center">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                    @endif
                </span>
                <span class="text-[10px] font-semibold text-gray-700">Alerts</span>
            </a>
        </div>
    </div>
</div>
@endsection
