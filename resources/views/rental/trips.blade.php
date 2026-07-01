@extends('layouts.rental')
@section('title', 'My Trips')
@section('page-title', 'My Trips')
@section('back', route('rental.dashboard'))
@section('content')
<div class="space-y-6">
    <p class="text-xs text-gray-500">Tap Start or End — daily KM is recorded separately under Daily KM Log.</p>

    <section class="space-y-3">
        <h2 class="emp-section-title">Active Trips</h2>
        @forelse($activeTrips as $trip)
            @include('rental.partials.trip-card', ['trip' => $trip, 'active' => true])
        @empty
            <p class="text-center text-gray-400 py-6 text-sm">No active trips assigned.</p>
        @endforelse
    </section>

    @if($completedTrips->isNotEmpty())
        <section class="space-y-3">
            <h2 class="emp-section-title">Recent Completed</h2>
            @foreach($completedTrips as $trip)
                @include('rental.partials.trip-card', ['trip' => $trip, 'active' => false])
            @endforeach
        </section>
    @endif
</div>
@endsection
