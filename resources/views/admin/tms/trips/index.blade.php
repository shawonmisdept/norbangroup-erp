@extends('layouts.admin')
@section('title', 'Trip Logs')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Trip Logs', 'subtitle' => 'Merged trips and duty times'])
<form method="GET" class="erp-panel p-4 mb-4 flex flex-wrap gap-3 items-end">
<div><label class="erp-label">Status</label>
<select name="trip_status" class="erp-input"><option value="">All</option>@foreach($statuses as $k => $l)<option value="{{ $k }}" @selected(($filters['trip_status'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
<button type="submit" class="erp-btn-primary">Filter</button>
</form>
<div class="erp-panel overflow-hidden">
<table class="erp-table">
<thead><tr><th>ID</th><th>Employees</th><th>Pickup</th><th>Pax</th><th>Vehicle</th><th>Driver</th><th>Duty</th><th>OT</th><th>Status</th><th></th></tr></thead>
<tbody>
@forelse($trips as $trip)
<tr>
<td class="tabular-nums">#{{ $trip->id }}</td>
<td class="text-xs">{{ $trip->transportRequests->pluck('employee.name')->filter()->implode(', ') ?: $trip->transportRequest?->employee?->name }}</td>
<td class="text-xs max-w-[140px]">
@php
    $pickups = $trip->transportRequests->pluck('pickup_location')->filter()->unique();
    if ($pickups->isEmpty() && $trip->transportRequest) {
        $pickups = collect([$trip->transportRequest->pickup_location]);
    }
@endphp
{{ $pickups->implode('; ') }}
</td>
<td class="tabular-nums">{{ $trip->total_passengers }}</td>
<td class="text-xs">{{ $trip->vehicle?->displayLabel() }}</td>
<td class="text-xs">{{ $trip->driver?->displayLabel() }}</td>
<td>@include('partials.erp.datetime-highlight', ['at' => $trip->duty_start_at, 'variant' => 'admin'])</td>
<td class="tabular-nums text-xs">৳{{ number_format($trip->ot_amount, 2) }}</td>
<td><span class="erp-badge {{ $trip->tripStatusBadgeClass() }}">{{ $trip->tripStatusLabel() }}</span></td>
<td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.tms.trips.show', $trip)])</td>
</tr>
@empty<tr><td colspan="10" class="text-center py-8 text-gray-400">No trips yet.</td></tr>@endforelse
</tbody></table>
@if($trips->hasPages())<div class="px-4 py-3 border-t">{{ $trips->links() }}</div>@endif
</div>
@endsection
