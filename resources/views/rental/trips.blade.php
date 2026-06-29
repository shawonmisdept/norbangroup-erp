@extends('layouts.rental')
@section('title', 'My Trips')
@section('page-title', 'My Trips')
@section('back', route('rental.dashboard'))
@section('content')
<div class="space-y-4">
<p class="text-xs text-gray-500">Tap Start or End — daily KM is recorded separately under Daily KM Log.</p>
@forelse($trips as $trip)
@php
    $requests = $trip->transportRequests->isNotEmpty()
        ? $trip->transportRequests->whereIn('status', ['approved', 'in_progress'])
        : collect($trip->transportRequest && in_array($trip->transportRequest->status, ['approved', 'in_progress'], true) ? [$trip->transportRequest] : []);
@endphp
<div class="emp-card p-4 space-y-3">
<p class="text-xs text-gray-500">{{ $trip->vehicle?->displayLabel() }} · {{ $trip->total_passengers }} passenger(s) · @include('partials.erp.tms-status-badge', ['status' => $trip->trip_status, 'label' => $trip->tripStatusLabel(), 'type' => 'trip', 'variant' => 'employee'])</p>

<div class="space-y-2">
@foreach($requests as $req)
<div class="rounded-lg border border-gray-100 bg-gray-50 p-3 text-sm">
<p class="font-medium">{{ $req->employee?->name }}</p>
<p class="text-xs mt-1"><span class="text-gray-500">Pickup:</span> {{ $req->pickup_location }}</p>
<p class="text-xs"><span class="text-gray-500">Destination:</span> {{ $req->destinationLabel() }}</p>
<p class="text-xs mt-2">@include('partials.erp.datetime-highlight', ['at' => $req->pickup_at, 'variant' => 'employee'])</p>
@if($req->purpose)<p class="text-xs text-gray-500">Purpose: {{ $req->purpose }}</p>@endif
</div>
@endforeach
</div>

@if($trip->trip_status === 'not_started')
<form method="POST" action="{{ route('rental.trips.start', $trip) }}">@csrf
<button type="submit" class="emp-btn w-full">Start Trip</button>
</form>
@elseif($trip->trip_status === 'in_progress')
<p class="text-xs">Started at @include('partials.erp.datetime-highlight', ['at' => $trip->duty_start_at, 'variant' => 'employee'])</p>
<form method="POST" action="{{ route('rental.trips.end', $trip) }}">@csrf
<button type="submit" class="emp-btn w-full">End Trip</button>
</form>
@endif
</div>
@empty
<p class="text-center text-gray-400 py-8 text-sm">No active trips assigned.</p>
@endforelse
</div>
@endsection
