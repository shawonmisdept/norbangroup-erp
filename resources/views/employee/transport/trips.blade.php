@extends('layouts.employee')
@section('title', 'Driver Trips')
@section('content')
<div class="space-y-4">
<div class="flex items-center justify-between">
<h1 class="text-lg font-bold">My Trips</h1>
<a href="{{ route('employee.transport.index') }}" class="emp-btn-secondary">← Back</a>
</div>
<p class="text-xs text-gray-500">Start/end trip times only — daily KM is recorded by admin separately.</p>
@forelse($trips as $trip)
@php
    $requests = $trip->transportRequests->isNotEmpty()
        ? $trip->transportRequests->whereIn('status', ['approved', 'in_progress'])
        : collect($trip->transportRequest && in_array($trip->transportRequest->status, ['approved', 'in_progress'], true) ? [$trip->transportRequest] : []);
@endphp
<div class="emp-card p-4 space-y-3">
<p class="text-xs text-gray-500 flex flex-wrap items-center gap-2">{{ $trip->vehicle?->displayLabel() }} · {{ $trip->total_passengers }} passenger(s) · @include('partials.erp.tms-status-badge', ['status' => $trip->trip_status, 'label' => $trip->tripStatusLabel(), 'type' => 'trip', 'variant' => 'employee'])</p>

<div class="space-y-2">
@foreach($requests as $req)
<div class="rounded-lg border border-gray-100 bg-gray-50 p-3 text-sm">
<p class="font-medium">{{ $req->employee?->name }}</p>
<p class="text-xs mt-1"><span class="text-gray-500">Pickup:</span> <span class="font-medium text-gray-900">{{ $req->pickup_location }}</span></p>
<p class="text-xs"><span class="text-gray-500">Destination:</span> {{ $req->destinationLabel() }}</p>
<p class="text-xs mt-2 flex flex-wrap items-center gap-2">
<span class="text-gray-500">Pickup time:</span>
@include('partials.erp.datetime-highlight', ['at' => $req->pickup_at, 'variant' => 'employee'])
<span class="text-gray-500">· {{ $req->passenger_count }} pax</span>
</p>
@if($req->purpose)<p class="text-xs text-gray-500">Purpose: {{ $req->purpose }}</p>@endif
</div>
@endforeach
</div>

@if($trip->trip_status === 'not_started')
<form method="POST" action="{{ route('employee.transport.trips.start', $trip) }}">@csrf
<button type="submit" class="emp-btn w-full">Start Trip</button>
</form>
@elseif($trip->trip_status === 'in_progress')
<p class="text-xs flex flex-wrap items-center gap-2">Started at @include('partials.erp.datetime-highlight', ['at' => $trip->duty_start_at, 'variant' => 'employee'])</p>
<form method="POST" action="{{ route('employee.transport.trips.end', $trip) }}">@csrf
<button type="submit" class="emp-btn w-full">End Trip</button>
</form>
@endif
</div>
@empty
<p class="text-center text-gray-400 py-8 text-sm">No active trips assigned.</p>
@endforelse
</div>
@endsection
