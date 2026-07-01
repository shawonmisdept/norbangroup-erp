@extends('layouts.employee')
@section('title', 'Request #' . $transportRequest->id)
@section('content')
<div class="space-y-4">
<a href="{{ route('employee.transport.index') }}" class="emp-btn-secondary">← Back</a>
<div class="emp-card p-4 space-y-2 text-sm">
<p><span class="text-gray-500">Status:</span> @include('partials.erp.tms-status-badge', ['status' => $transportRequest->status, 'label' => $transportRequest->statusLabel(), 'variant' => 'employee'])</p>
<p><span class="text-gray-500">Pickup:</span> {{ $transportRequest->pickup_location }}</p>
<p><span class="text-gray-500">Destination:</span> {{ $transportRequest->destinationLabel() }}</p>
<p class="flex flex-wrap items-center gap-2"><span class="text-gray-500">When:</span> @include('partials.erp.datetime-highlight', ['at' => $transportRequest->pickup_at, 'variant' => 'employee'])</p>
<p><span class="text-gray-500">Purpose:</span> {{ $transportRequest->purpose }}</p>
@if($transportRequest->rejection_reason)<p class="text-red-600"><span class="text-gray-500">Rejected:</span> {{ $transportRequest->rejection_reason }}</p>@endif
@if($transportRequest->vehicle)<p><span class="text-gray-500">Vehicle:</span> {{ $transportRequest->vehicle->displayLabel() }}</p>@endif
</div>

@if(in_array($transportRequest->status, ['approved', 'in_progress']) && ($transportRequest->driver || $transportRequest->rentalDriver))
@php
    $isRental = (bool) $transportRequest->rentalDriver;
    $driverName = $isRental
        ? $transportRequest->rentalDriver->name
        : $transportRequest->driver->displayLabel();
    $driverPhone = $isRental
        ? $transportRequest->rentalDriver->contactPhone()
        : $transportRequest->driver->contactPhone();
@endphp
<div class="emp-card p-4 space-y-2 border border-indigo-100 bg-indigo-50/50">
<p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">
    Assigned Driver
    @if($isRental)
        <span class="normal-case font-medium text-indigo-600">(Rental)</span>
    @endif
</p>
<p class="font-medium text-gray-900">{{ $driverName }}</p>
@if($isRental && $transportRequest->rentalDriver->vendorLabel())
<p class="text-xs text-gray-600">{{ $transportRequest->rentalDriver->vendorLabel() }}</p>
@endif
@if($driverPhone)
<p class="text-sm tabular-nums text-gray-700">{{ $driverPhone }}</p>
<a href="tel:{{ preg_replace('/[^0-9+]/', '', $driverPhone) }}" class="emp-btn w-full text-center">Call {{ $driverName }}</a>
@else
<p class="text-xs text-gray-500">Driver phone not on file — contact admin.</p>
@endif
</div>
@endif

@if($transportRequest->canBeCancelledByEmployee())
<form method="POST" action="{{ route('employee.transport.requests.cancel', $transportRequest) }}">@csrf
<button type="submit" class="emp-btn-secondary w-full" data-confirm="{{ $transportRequest->status === 'approved' ? 'Cancel this approved trip? The driver will be notified.' : 'Cancel this request?' }}">Cancel Request</button>
</form>
@elseif($transportRequest->status === 'approved')
<p class="text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg p-3">Trip has started — contact transport admin to cancel.</p>
@endif

@if($transportRequest->status === 'pending')
<a href="{{ route('employee.transport.requests.edit', $transportRequest) }}" class="emp-btn w-full text-center block">Edit Request</a>
@endif

@include('employee.transport.partials.request-history', ['histories' => $transportRequest->histories])
</div>
@endsection
