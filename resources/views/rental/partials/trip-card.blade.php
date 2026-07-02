@php
    $requests = $trip->transportRequests->isNotEmpty()
        ? $trip->transportRequests->whereIn('status', $active ? ['approved', 'in_progress'] : ['completed'])
        : collect($trip->transportRequest && in_array($trip->transportRequest->status, $active ? ['approved', 'in_progress'] : ['completed'], true) ? [$trip->transportRequest] : []);
@endphp
<div class="emp-card p-4 space-y-3">
    <p class="text-xs text-gray-500">{{ $trip->vehicle?->displayLabel() }} · {{ $trip->total_passengers }} passenger(s) · @include('partials.erp.tms-status-badge', ['status' => $trip->trip_status, 'label' => $trip->tripStatusLabel(), 'type' => 'trip', 'variant' => 'employee'])</p>

    <div class="space-y-2">
        @forelse($requests as $req)
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 text-sm">
                <p class="font-medium">{{ $req->employee?->name }}</p>
                <p class="text-xs mt-1"><span class="text-gray-500">Pickup:</span> {{ $req->pickup_location }}</p>
                <p class="text-xs"><span class="text-gray-500">Destination:</span> {{ $req->destinationLabel() }}</p>
                <p class="text-xs mt-2">@include('partials.erp.datetime-highlight', ['at' => $req->pickup_at, 'variant' => 'employee'])</p>
                @if($req->purpose)<p class="text-xs text-gray-500">Purpose: {{ $req->purpose }}</p>@endif
            </div>
        @empty
            <p class="text-xs text-gray-400">No passenger details on file.</p>
        @endforelse
    </div>

    @if($active)
        @if($trip->trip_status === 'not_started')
            <form method="POST" action="{{ route('rental.trips.start', $trip) }}" data-tms-trip-gps>@csrf
                @include('partials.tms.trip-gps-fields')
                <button type="submit" class="emp-btn w-full">Start Trip</button>
            </form>
        @elseif($trip->trip_status === 'in_progress')
            <p class="text-xs">Started at @include('partials.erp.datetime-highlight', ['at' => $trip->duty_start_at, 'variant' => 'employee'])</p>
            <form method="POST" action="{{ route('rental.trips.end', $trip) }}" data-tms-trip-gps>@csrf
                @include('partials.tms.trip-gps-fields')
                <button type="submit" class="emp-btn w-full">End Trip</button>
            </form>
        @endif
    @else
        <p class="text-xs text-gray-500">
            @if($trip->duty_start_at)
                Started @include('partials.erp.datetime-highlight', ['at' => $trip->duty_start_at, 'variant' => 'employee'])
            @endif
            @if($trip->duty_end_at)
                · Ended @include('partials.erp.datetime-highlight', ['at' => $trip->duty_end_at, 'variant' => 'employee'])
            @endif
        </p>
    @endif
</div>
