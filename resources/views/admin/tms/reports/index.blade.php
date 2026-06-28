@extends('layouts.admin')
@section('title', 'TMS Reports')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'TMS Reports', 'subtitle' => 'Filter and view all logs on screen'])
@php $tabs = ['requests' => 'Requests', 'trips' => 'Trips', 'fuel' => 'Fuel', 'odometer' => 'Daily KM', 'ot' => 'OT Payments']; @endphp
<div class="flex flex-wrap gap-2 mb-4">
@foreach($tabs as $key => $label)
<a href="{{ route('admin.tms.reports.index', array_merge($filters, ['tab' => $key])) }}"
   class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $tab === $key ? 'bg-brand text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">{{ $label }}</a>
@endforeach
</div>
<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
<input type="hidden" name="tab" value="{{ $tab }}">
<div><label class="erp-label">From</label><input type="date" name="from" class="erp-input" value="{{ $filters['from'] ?? '' }}"></div>
<div><label class="erp-label">To</label><input type="date" name="to" class="erp-input" value="{{ $filters['to'] ?? '' }}"></div>
@if($factories !== [])
<div><label class="erp-label">Unit</label><select name="factory_id" class="erp-input"><option value="">All</option>@foreach($factories as $id => $name)<option value="{{ $id }}" @selected(($filters['factory_id'] ?? '') == $id)>{{ $name }}</option>@endforeach</select></div>
@endif
@if($tab === 'requests')
<div><label class="erp-label">Status</label><select name="status" class="erp-input"><option value="">All</option>@foreach($statuses as $k => $l)<option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
@endif
<div class="flex gap-2">
<button type="submit" class="erp-btn-primary">Apply</button>
<a href="{{ route('admin.tms.reports.export', array_merge($filters, ['report' => $tab])) }}" class="erp-btn-secondary">Export CSV</a>
</div>
</form>
<div class="erp-panel overflow-hidden">
<table class="erp-table">
@if($tab === 'requests')
<thead><tr><th>ID</th><th>Employee</th><th>Destination</th><th>When</th><th>Pax</th><th>Status</th><th>Trip</th><th></th></tr></thead>
<tbody>@forelse($rows as $r)<tr>
<td>#{{ $r->id }}</td><td>{{ $r->employee?->name }}</td><td class="text-xs">{{ $r->destinationLabel() }}</td>
<td>@include('partials.erp.datetime-highlight', ['at' => $r->pickup_at, 'variant' => 'admin'])</td><td>{{ $r->passenger_count }}</td><td><span class="erp-badge {{ $r->statusBadgeClass() }}">{{ $r->statusLabel() }}</span></td>
<td>@if($r->trip_log_id)<a href="{{ route('admin.tms.trips.show', $r->trip_log_id) }}" class="erp-btn-sm-secondary">#{{ $r->trip_log_id }}</a>@else — @endif</td>
<td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.tms.requests.show', $r)])</td>
</tr>@empty<tr><td colspan="8" class="text-center py-8 text-gray-400">No records.</td></tr>@endforelse</tbody>
@elseif($tab === 'trips')
<thead><tr><th>ID</th><th>Employees</th><th>Pax</th><th>Vehicle</th><th>Driver</th><th>Duty End</th><th>OT</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rows as $t)<tr>
<td class="tabular-nums">#{{ $t->id }}</td>
<td class="text-xs">{{ $t->transportRequests->pluck('employee.name')->filter()->implode(', ') }}</td>
<td>{{ $t->total_passengers }}</td><td class="text-xs">{{ $t->vehicle?->displayLabel() }}</td>
<td class="text-xs">{{ $t->driver?->displayLabel() }}</td><td>@include('partials.erp.datetime-highlight', ['at' => $t->duty_end_at, 'variant' => 'admin'])</td>
<td>৳{{ number_format($t->ot_amount, 2) }}</td><td><span class="erp-badge {{ $t->tripStatusBadgeClass() }}">{{ $t->tripStatusLabel() }}</span></td>
<td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.tms.trips.show', $t)])</td>
</tr>@empty<tr><td colspan="9" class="text-center py-8 text-gray-400">No records.</td></tr>@endforelse</tbody>
@elseif($tab === 'fuel')
<thead><tr><th>Date</th><th>Vehicle</th><th>Type</th><th>Qty</th><th>Amount</th><th>Paid By</th></tr></thead>
<tbody>@forelse($rows as $f)<tr>
<td class="text-xs">{{ $f->created_at?->format('d M Y') }}</td><td class="text-xs">{{ $f->vehicle?->displayLabel() }}</td>
<td>{{ $f->fuel_type }}</td><td>{{ $f->quantity }}</td><td>৳{{ number_format($f->amount, 2) }}</td><td>{{ $f->paid_by }}</td>
</tr>@empty<tr><td colspan="6" class="text-center py-8 text-gray-400">No records.</td></tr>@endforelse</tbody>
@elseif($tab === 'odometer')
<thead><tr><th>Date</th><th>Vehicle</th><th>Morning</th><th>Evening</th><th>Daily KM</th></tr></thead>
<tbody>@forelse($rows as $o)<tr>
<td>{{ $o->log_date?->format('d M Y') }}</td><td>{{ $o->vehicle?->displayLabel() }}</td>
<td>{{ $o->morning_km ?? '—' }}</td><td>{{ $o->evening_km ?? '—' }}</td><td>{{ $o->dailyKm() ?? '—' }}</td>
</tr>@empty<tr><td colspan="5" class="text-center py-8 text-gray-400">No records.</td></tr>@endforelse</tbody>
@else
<thead><tr><th>Trip</th><th>Driver</th><th>Amount</th><th>Status</th><th>Paid At</th></tr></thead>
<tbody>@forelse($rows as $p)<tr>
<td>#{{ $p->trip_log_id }}</td><td>{{ $p->driver?->displayLabel() }}</td>
<td>৳{{ number_format($p->amount, 2) }}</td><td>{{ $p->payment_status }}</td><td>{{ $p->paid_at?->format('d M Y') ?? '—' }}</td>
</tr>@empty<tr><td colspan="5" class="text-center py-8 text-gray-400">No records.</td></tr>@endforelse</tbody>
@endif
</table>
@if($rows->hasPages())<div class="px-4 py-3 border-t">{{ $rows->links() }}</div>@endif
</div>
@endsection
