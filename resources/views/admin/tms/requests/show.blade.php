@extends('layouts.admin')
@section('title', 'Request #' . $transportRequest->id)
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Request #' . $transportRequest->id,
    'subtitle' => 'Transport request details',
    'actions' => '<a href="' . route('admin.tms.requests.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 erp-panel p-6 space-y-3 text-sm">
        <p class="flex flex-wrap items-center gap-2">
            <span class="text-gray-500">Status:</span>
            @include('partials.erp.tms-status-badge', ['status' => $transportRequest->status, 'label' => $transportRequest->statusLabel()])
        </p>
        <p><span class="text-gray-500">Employee:</span> {{ $transportRequest->employee?->name }}</p>
        <p><span class="text-gray-500">Pickup:</span> {{ $transportRequest->pickup_location }}</p>
        <p><span class="text-gray-500">Destination:</span> {{ $transportRequest->destinationLabel() }}</p>
        <p class="flex flex-wrap items-center gap-2">
            <span class="text-gray-500">Pickup Time:</span>
            @include('partials.erp.datetime-highlight', ['at' => $transportRequest->pickup_at, 'variant' => 'admin'])
        </p>
        <p><span class="text-gray-500">Purpose:</span> {{ $transportRequest->purpose }}</p>
        <p><span class="text-gray-500">Passengers:</span> {{ $transportRequest->passenger_count }}</p>

        @if($transportRequest->trip_log_id)
            <p class="flex flex-wrap items-center gap-2">
                <span class="text-gray-500">Trip:</span>
                <a href="{{ route('admin.tms.trips.show', $transportRequest->trip_log_id) }}" class="erp-btn-sm-secondary">View Trip #{{ $transportRequest->trip_log_id }}</a>
            </p>
        @endif

        @if($transportRequest->rejection_reason)
            <p class="text-red-600"><span class="text-gray-500">Rejection:</span> {{ $transportRequest->rejection_reason }}</p>
        @endif

        @if($transportRequest->vehicle)
            <p><span class="text-gray-500">Vehicle:</span> {{ $transportRequest->vehicle->displayLabel() }}</p>
        @endif

        @if($transportRequest->driver)
            <p>
                <span class="text-gray-500">Driver:</span> {{ $transportRequest->driver->displayLabel() }}
                @if($transportRequest->driver->contactPhone())
                    · <a href="tel:{{ preg_replace('/[^0-9+]/', '', $transportRequest->driver->contactPhone()) }}" class="text-indigo-600">{{ $transportRequest->driver->contactPhone() }}</a>
                @endif
            </p>
        @endif

        @if($transportRequest->tripLog)
            <div class="border-t pt-3 mt-3">
                <p class="font-medium mb-2">Trip Log</p>
                <p class="flex flex-wrap items-center gap-2">
                    Duty:
                    @include('partials.erp.datetime-highlight', ['at' => $transportRequest->tripLog->duty_start_at, 'variant' => 'admin'])
                    <span class="text-gray-400">→</span>
                    @include('partials.erp.datetime-highlight', ['at' => $transportRequest->tripLog->duty_end_at, 'variant' => 'admin'])
                </p>
                <p>OT: {{ $transportRequest->tripLog->ot_hours }} hrs · ৳{{ number_format($transportRequest->tripLog->ot_amount, 2) }}</p>

                @if($transportRequest->tripLog->transportRequests->count() > 1)
                    <p class="mt-2 text-xs text-gray-500">
                        Merged with:
                        @foreach($transportRequest->tripLog->transportRequests as $r)
                            {{ $r->employee?->name }}@if(!$loop->last), @endif
                        @endforeach
                    </p>
                @endif
            </div>
        @endif

        @include('admin.tms.partials.request-history', ['histories' => $transportRequest->histories])
    </div>

    <div class="space-y-4">
        @if($transportRequest->status === 'pending' && auth()->user()->hasPermission('tms.requests.approve'))
            @include('admin.tms.requests.partials.approve-form')

            <div class="erp-panel p-6">
                <h3 class="font-semibold mb-3">Reject</h3>
                <form method="POST" action="{{ route('admin.tms.requests.reject', $transportRequest) }}" class="space-y-3"
                      data-confirm="Reject this transport request?"
                      data-confirm-variant="danger"
                      data-confirm-ok="Yes, reject">
                    @csrf
                    <div>
                        <label class="erp-label">Reason</label>
                        <textarea name="rejection_reason" class="erp-input" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="erp-btn-secondary w-full">Reject</button>
                </form>
            </div>
        @endif

        @if($transportRequest->status === 'approved' && auth()->user()->hasPermission('tms.requests.approve'))
            @php
                $tripNotStarted = $transportRequest->tripLog?->trip_status === 'not_started';
            @endphp

            @if($tripNotStarted)
                <div class="erp-panel p-6">
                    <h3 class="font-semibold mb-3">Reassign Driver / Vehicle</h3>
                    <form method="POST" action="{{ route('admin.tms.requests.reassign', $transportRequest) }}" class="space-y-3"
                          data-confirm="Reassign driver and vehicle for this trip?"
                          data-confirm-variant="warning"
                          data-confirm-ok="Yes, reassign">
                        @csrf
                        @include('admin.tms.requests.partials.driver-assignment-fields', [
                            'drivers' => $drivers,
                            'rentalDrivers' => $rentalDrivers,
                            'vehicles' => $vehicles,
                            'vehiclePaperWarnings' => $vehiclePaperWarnings ?? [],
                            'passengerCount' => $transportRequest->tripLog?->total_passengers ?? $transportRequest->passenger_count,
                        ])
                        <button type="submit" class="erp-btn-primary w-full">Reassign</button>
                    </form>
                </div>
                @include('admin.tms.requests.partials.driver-assignment-script')

                <div class="erp-panel p-6">
                    <h3 class="font-semibold mb-3">Cancel Approved Request</h3>
                    <form method="POST" action="{{ route('admin.tms.requests.cancel', $transportRequest) }}" class="space-y-3"
                          data-confirm="Cancel this approved request before the trip starts?"
                          data-confirm-variant="danger"
                          data-confirm-ok="Yes, cancel">
                        @csrf
                        <div>
                            <label class="erp-label">Reason (optional)</label>
                            <textarea name="reason" class="erp-input" rows="2" placeholder="Reason for cancellation…"></textarea>
                        </div>
                        <button type="submit" class="erp-btn-secondary w-full">Cancel Request</button>
                    </form>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
