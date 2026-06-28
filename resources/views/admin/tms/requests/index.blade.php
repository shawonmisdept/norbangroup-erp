@extends('layouts.admin')
@section('title', 'Transport Requests')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Transport Requests', 'subtitle' => 'Select pending requests to merge and assign by driver vehicle capacity'])
@include('admin.tms.partials.request-filters', ['filters' => $filters, 'factories' => $factories, 'statuses' => $statuses])

@if(auth()->user()->hasPermission('tms.requests.approve'))
<form method="POST" action="{{ route('admin.tms.requests.merge') }}" id="merge-form" class="erp-panel p-4 mb-4 space-y-3">
@csrf
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
<div>
<label class="erp-label">Driver (who will actually drive)</label>
<select name="driver_id" id="merge-driver" class="erp-input" required>
<option value="">Select driver…</option>
@foreach($drivers as $d)
<option value="{{ $d->id }}" data-vehicle="{{ $d->default_vehicle_id }}" data-capacity="{{ $d->defaultVehicle?->passenger_capacity ?? 0 }}">
{{ $d->displayLabel() }} — default: {{ $d->defaultVehicle?->displayLabel() ?? 'No vehicle' }}
</option>
@endforeach
</select>
</div>
<div>
<label class="erp-label">Vehicle</label>
<select name="vehicle_id" id="merge-vehicle" class="erp-input">
<option value="">Use driver's default vehicle</option>
@foreach($vehicles as $v)
<option value="{{ $v->id }}" data-capacity="{{ $v->passenger_capacity }}">{{ $v->displayLabel() }} ({{ $v->passenger_capacity }} seats)</option>
@endforeach
</select>
</div>
<div class="flex gap-2">
<button type="submit" class="erp-btn-primary flex-1" data-confirm="Merge selected requests and assign to this driver?">Merge & Assign</button>
</div>
</div>
<p class="text-xs text-gray-500">Select the driver who will drive today — change vehicle if they use a different car. Selected passengers: <strong id="selected-passengers">0</strong> · Vehicle capacity: <strong id="vehicle-capacity">—</strong></p>
</form>
@endif

<div class="erp-panel overflow-hidden">
<table class="erp-table">
<thead><tr>
@if(auth()->user()->hasPermission('tms.requests.approve'))<th class="w-8"></th>@endif
<th>ID</th><th>Employee</th><th>Pickup</th><th>Destination</th><th>When</th><th>Pax</th><th>Status</th><th></th>
</tr></thead>
<tbody>
@forelse($requests as $req)
<tr>
@if(auth()->user()->hasPermission('tms.requests.approve'))
<td>@if($req->status === 'pending')<input type="checkbox" form="merge-form" name="request_ids[]" value="{{ $req->id }}" class="merge-check rounded" data-passengers="{{ $req->passenger_count }}">@endif</td>
@endif
<td class="tabular-nums">#{{ $req->id }}</td>
<td>{{ $req->employee?->name }}</td>
<td class="text-xs max-w-[160px]">{{ $req->pickup_location }}</td>
<td class="text-xs">{{ $req->destinationLabel() }}</td>
<td>@include('partials.erp.datetime-highlight', ['at' => $req->pickup_at, 'variant' => 'admin'])</td>
<td class="tabular-nums text-center">{{ $req->passenger_count }}</td>
<td><span class="erp-badge {{ $req->statusBadgeClass() }}">{{ $req->statusLabel() }}</span></td>
<td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.tms.requests.show', $req)])</td>
</tr>
@empty<tr><td colspan="9" class="text-center py-8 text-gray-400">No requests.</td></tr>@endforelse
</tbody></table>
@if($requests->hasPages())<div class="px-4 py-3 border-t">{{ $requests->links() }}</div>@endif
</div>

@if(auth()->user()->hasPermission('tms.requests.approve'))
<script>
document.addEventListener('DOMContentLoaded', () => {
    const driver = document.getElementById('merge-driver');
    const capEl = document.getElementById('vehicle-capacity');
    const paxEl = document.getElementById('selected-passengers');
    const checks = () => document.querySelectorAll('.merge-check:checked');

    function refresh() {
        let total = 0;
        checks().forEach(c => total += parseInt(c.dataset.passengers || '1', 10));
        paxEl.textContent = total;
    }

    driver?.addEventListener('change', () => {
        const opt = driver.selectedOptions[0];
        const vehicle = document.getElementById('merge-vehicle');
        const defaultVehicleId = opt?.dataset.vehicle;
        if (vehicle && defaultVehicleId) {
            vehicle.value = defaultVehicleId;
        }
        refreshCapacity();
    });

    document.getElementById('merge-vehicle')?.addEventListener('change', refreshCapacity);

    function refreshCapacity() {
        const vehicle = document.getElementById('merge-vehicle');
        const opt = vehicle?.selectedOptions[0];
        if (opt?.value) {
            capEl.textContent = opt.dataset.capacity || '—';
            return;
        }
        const driverOpt = driver?.selectedOptions[0];
        capEl.textContent = driverOpt?.dataset.capacity || '—';
    }

    document.querySelectorAll('.merge-check').forEach(c => c.addEventListener('change', refresh));
    refreshCapacity();
});
</script>
@endif
@endsection
