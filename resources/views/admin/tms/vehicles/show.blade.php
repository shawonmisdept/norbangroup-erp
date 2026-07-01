@extends('layouts.admin')
@section('title', $vehicle->displayLabel())
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $vehicle->displayLabel(),
    'subtitle' => 'Vehicle profile & recent trips',
    'actions' => '<a href="' . route('admin.tms.vehicles.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 erp-panel p-6 space-y-4 text-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Unit</span>
                <span class="font-medium">{{ $vehicle->factory?->name ?? '—' }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Status</span>
                <span class="erp-badge {{ $vehicle->statusBadgeClass() }}">{{ $vehicle->statusLabel() }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Registration</span>
                <span class="font-medium tabular-nums">{{ $vehicle->reg_number }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Type</span>
                <span class="font-medium capitalize">{{ $vehicle->type }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Fuel</span>
                <span class="font-medium capitalize">{{ $vehicle->fuel_type }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Capacity</span>
                <span class="font-medium tabular-nums">{{ $vehicle->passenger_capacity }} seats</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Last Odometer</span>
                <span class="font-medium tabular-nums">{{ number_format((float) $vehicle->last_odometer_km, 2) }} km</span>
            </div>
            @if($vehicle->isRental())
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Rental Vendor</span>
                    <span class="font-medium">{{ $vehicle->rentalVendor?->name ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">KM Rate</span>
                    <span class="font-medium tabular-nums">৳{{ number_format((float) $vehicle->rental_km_rate, 2) }}</span>
                </div>
            @endif
            @if($vehicle->allocatedUserLabel())
                <div class="sm:col-span-2">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Allocated To</span>
                    <span class="font-medium">{{ $vehicle->allocatedUserLabel() }}</span>
                </div>
            @endif
            <div class="sm:col-span-2">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Default Drivers</span>
                <span class="font-medium">{{ $vehicle->assignedDriverNames() }}</span>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 pt-2 border-t border-erp-border">
            @if(auth()->user()->canViewTmsSubmodule('maintenance'))
                <a href="{{ route('admin.tms.maintenance.register', $vehicle) }}" class="erp-btn-sm-secondary">Maintenance Register</a>
            @endif
            @if($canManage)
                <a href="{{ route('admin.tms.vehicles.edit', $vehicle) }}" class="erp-btn-sm-primary">Edit Vehicle</a>
            @endif
        </div>
    </div>

    <div class="erp-panel p-6">
        <h3 class="font-semibold mb-3">Recent Trips</h3>
        @forelse($recentTrips as $trip)
            <div class="py-2 border-b border-gray-100 last:border-0 text-sm">
                <a href="{{ route('admin.tms.trips.show', $trip) }}" class="font-medium text-indigo-600">Trip #{{ $trip->id }}</a>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $trip->tripStatusLabel() }}
                    @if($trip->duty_start_at)
                        · {{ $trip->duty_start_at->format('d M Y') }}
                    @endif
                </p>
            </div>
        @empty
            <p class="text-sm text-gray-400">No trips recorded yet.</p>
        @endforelse
    </div>
</div>
@endsection
