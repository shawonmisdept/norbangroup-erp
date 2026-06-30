@extends('layouts.admin')
@section('title', 'Trip #' . $trip->id)
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Trip #' . $trip->id,
    'subtitle' => 'Trip log details',
    'actions' => '<a href="' . route('admin.tms.trips.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="erp-panel p-6 max-w-3xl space-y-3 text-sm">
    <p class="flex flex-wrap items-center gap-2">
        <span class="text-gray-500">Status:</span>
        @include('partials.erp.tms-status-badge', ['status' => $trip->trip_status, 'label' => $trip->tripStatusLabel(), 'type' => 'trip'])
    </p>

    <p>
        <span class="text-gray-500">Vehicle:</span> {{ $trip->vehicle?->displayLabel() }}
        @if($trip->vehicle?->isRental())
            <span class="erp-badge bg-amber-100 text-amber-800">Rental</span>
        @endif
    </p>

    @if($trip->vehicle?->rentalVendor)
        <p><span class="text-gray-500">Vendor:</span> {{ $trip->vehicle->rentalVendor->name }}</p>
    @endif

    <p><span class="text-gray-500">Driver:</span> {{ $trip->assignedDriverLabel() }}</p>
    <p><span class="text-gray-500">Total passengers:</span> {{ $trip->total_passengers }}</p>

    <div>
        <p class="text-gray-500 mb-2">Passengers & pickup:</p>
        <ul class="space-y-2">
            @forelse($trip->transportRequests as $r)
                <li class="rounded border border-gray-100 bg-gray-50 px-3 py-2 flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <span class="font-medium text-gray-900">{{ $r->employee?->name }}</span>
                        <span class="text-gray-500"> — Pickup:</span> <strong>{{ $r->pickup_location }}</strong>
                        <span class="text-gray-500"> →</span> {{ $r->destinationLabel() }}
                        <span class="text-gray-400 text-xs">@include('partials.erp.datetime-highlight', ['at' => $r->pickup_at, 'variant' => 'admin'])</span>
                    </div>
                    <a href="{{ route('admin.tms.requests.show', $r) }}" class="erp-btn-sm-secondary shrink-0">View</a>
                </li>
            @empty
                @if($trip->transportRequest)
                    <li class="rounded border border-gray-100 bg-gray-50 px-3 py-2 flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <span class="font-medium text-gray-900">{{ $trip->transportRequest->employee?->name }}</span>
                            <span class="text-gray-500"> — Pickup:</span> <strong>{{ $trip->transportRequest->pickup_location }}</strong>
                            <span class="text-gray-500"> →</span> {{ $trip->transportRequest->destinationLabel() }}
                        </div>
                        <a href="{{ route('admin.tms.requests.show', $trip->transportRequest) }}" class="erp-btn-sm-secondary shrink-0">View</a>
                    </li>
                @else
                    <li>—</li>
                @endif
            @endforelse
        </ul>
    </div>

    <p class="flex flex-wrap items-center gap-2">
        <span class="text-gray-500">Duty:</span>
        @include('partials.erp.datetime-highlight', ['at' => $trip->duty_start_at, 'variant' => 'admin'])
        <span class="text-gray-400">→</span>
        @include('partials.erp.datetime-highlight', ['at' => $trip->duty_end_at, 'variant' => 'admin'])
    </p>

    <p>
        <span class="text-gray-500">KM:</span>
        @if($trip->vehicle?->isRental())
            <span class="text-xs text-amber-700">Daily odometer (Morning/Evening) — not per trip</span>
        @elseif($trip->start_km !== null)
            {{ number_format($trip->start_km, 2) }} → {{ $trip->end_km !== null ? number_format($trip->end_km, 2) : '—' }}
            @if($trip->total_km !== null)
                · <strong>{{ number_format($trip->total_km, 2) }} km</strong>
            @endif
        @else
            —
        @endif
        @if($trip->vehicle)
            <span class="text-xs text-gray-400">(last odometer: {{ number_format($trip->vehicle->last_odometer_km, 2) }})</span>
        @endif
    </p>

    <div class="border-t pt-3 mt-3">
        <p class="text-gray-500 mb-2 font-medium">Driver Pay</p>
        @if($trip->total_driver_pay > 0 || $trip->bill_type !== 'none')
            <ul class="space-y-1 text-sm">
                @if($trip->night_bill_amount > 0)
                    <li>Night Bill: ৳{{ number_format($trip->night_bill_amount, 2) }}</li>
                @endif
                @if($trip->holiday_duty_amount > 0)
                    <li>Holiday Duty: ৳{{ number_format($trip->holiday_duty_amount, 2) }}</li>
                @endif
                @if($trip->ot_hours > 0)
                    <li>Hourly OT: {{ $trip->ot_hours }} hrs · ৳{{ number_format($trip->ot_hourly_amount, 2) }}</li>
                @endif
                <li><strong>Total: ৳{{ number_format($trip->total_driver_pay ?: $trip->ot_amount, 2) }}</strong></li>
            </ul>
        @else
            <p>—</p>
        @endif
    </div>

    @if($trip->overtimePayment)
        <p>
            <span class="text-gray-500">Driver Payment:</span> {{ ucfirst($trip->overtimePayment->payment_status) }}
            @if($trip->overtimePayment->payment_status === 'pending' && auth()->user()->hasPermission('tms.overtime.manage'))
                <form method="POST" action="{{ route('admin.tms.trips.mark-ot-paid', $trip) }}" class="inline ml-2">
                    @csrf
                    <button type="submit" class="erp-btn-sm-primary" data-confirm="Mark driver payment as paid?">Mark Paid</button>
                </form>
            @endif
        </p>
    @endif
</div>

@if($trip->trip_status === 'not_started' && auth()->user()->canManageTmsSubmodule('settings') && ! $trip->rental_driver_id)
    <div class="erp-panel p-6 max-w-3xl mt-4">
        <h3 class="font-semibold mb-3">Start Trip (Admin)</h3>
        <form method="POST" action="{{ route('admin.tms.trips.start', $trip) }}" class="space-y-3">
            @csrf
            @if(! $trip->vehicle?->isRental())
                <div>
                    <label class="erp-label">Start KM</label>
                    <input type="number" step="0.01" min="0" name="start_km" class="erp-input" placeholder="Min {{ number_format($trip->vehicle?->last_odometer_km ?? 0, 2) }}">
                </div>
            @endif
            <button type="submit" class="erp-btn-primary">Start Trip</button>
        </form>
    </div>
@elseif($trip->trip_status === 'in_progress' && auth()->user()->canManageTmsSubmodule('settings') && ! $trip->rental_driver_id)
    <div class="erp-panel p-6 max-w-3xl mt-4">
        <h3 class="font-semibold mb-3">End Trip (Admin)</h3>
        <form method="POST" action="{{ route('admin.tms.trips.end', $trip) }}" class="space-y-3">
            @csrf
            @if($trip->start_km !== null)
                <p class="text-xs text-gray-500">Start KM: {{ number_format($trip->start_km, 2) }}</p>
            @endif
            @if(! $trip->vehicle?->isRental())
                <div>
                    <label class="erp-label">End KM</label>
                    <input type="number" step="0.01" min="0" name="end_km" class="erp-input">
                </div>
            @endif
            <button type="submit" class="erp-btn-primary">End Trip</button>
        </form>
    </div>
@elseif($trip->rental_driver_id && in_array($trip->trip_status, ['not_started', 'in_progress']))
    <div class="erp-panel p-6 max-w-3xl mt-4 text-sm text-amber-800 bg-amber-50">
        Rental driver manages this trip via the <strong>Rental Driver Portal</strong> (<code>/rental/login</code>).
    </div>
@endif
@endsection
