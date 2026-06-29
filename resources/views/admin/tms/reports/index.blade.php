@extends('layouts.admin')
@section('title', 'TMS Reports')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'TMS Reports', 'subtitle' => 'Filter and view all logs on screen'])
@php
$tabs = [
    'requests'       => 'Requests',
    'trips'          => 'Trips',
    'fuel'           => 'Fuel',
    'odometer'       => 'Daily KM',
    'ot'             => 'Driver Pay',
    'maintenance'    => 'Maintenance',
    'rental_charges' => 'Rental Charges',
    'fleet_cost'     => 'Fleet Cost',
];
@endphp
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
@if(in_array($tab, ['ot', 'rental_charges']))
<div><label class="erp-label">Payment</label><select name="payment_status" class="erp-input"><option value="">All</option><option value="pending" @selected(($filters['payment_status'] ?? '') === 'pending')>Pending</option><option value="paid" @selected(($filters['payment_status'] ?? '') === 'paid')>Paid</option></select></div>
@endif
<div class="flex gap-2">
<button type="submit" class="erp-btn-primary">Apply</button>
<a href="{{ route('admin.tms.reports.export', array_merge($filters, ['report' => $tab])) }}" class="erp-btn-secondary">Export CSV</a>
</div>
</form>

@if($tab === 'fleet_cost' && $summary)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Fuel</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['fuel_total'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">Company ৳{{ number_format($summary['fuel_company'], 2) }} · Rental ৳{{ number_format($summary['fuel_rental_party'], 2) }}</p>
    </div>
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Rental Vehicle Charges</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['rental_charges_total'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">Paid ৳{{ number_format($summary['rental_charges_paid'], 2) }} · Pending ৳{{ number_format($summary['rental_charges_pending'], 2) }}</p>
    </div>
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Driver Pay</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['driver_pay_total'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">Paid ৳{{ number_format($summary['driver_pay_paid'], 2) }} · Pending ৳{{ number_format($summary['driver_pay_pending'], 2) }}</p>
    </div>
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Maintenance</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['maintenance_total'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">Company ৳{{ number_format($summary['maintenance_company'], 2) }} · Rental ৳{{ number_format($summary['maintenance_rental_party'], 2) }}</p>
    </div>
    <div class="erp-panel p-4 md:col-span-2 lg:col-span-2 bg-brand/5 border-brand/20">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Grand Total</p>
        <p class="text-3xl font-bold tabular-nums text-brand">৳{{ number_format($summary['grand_total'], 2) }}</p>
    </div>
</div>
@else
<div class="erp-panel overflow-hidden">
<table class="erp-table">
@if($tab === 'requests')
<thead><tr><th>ID</th><th>Employee</th><th>Destination</th><th>When</th><th>Pax</th><th>Status</th><th>Trip</th><th>Driver</th><th></th></tr></thead>
<tbody>@forelse($rows as $r)<tr>
<td>#{{ $r->id }}</td><td>{{ $r->employee?->name }}</td><td class="text-xs">{{ $r->destinationLabel() }}</td>
<td>@include('partials.erp.datetime-highlight', ['at' => $r->pickup_at, 'variant' => 'admin'])</td><td>{{ $r->passenger_count }}</td><td><span class="erp-badge {{ $r->statusBadgeClass() }}">{{ $r->statusLabel() }}</span></td>
<td>@if($r->trip_log_id)<a href="{{ route('admin.tms.trips.show', $r->trip_log_id) }}" class="erp-btn-sm-secondary">#{{ $r->trip_log_id }}</a>@else — @endif</td>
<td class="text-xs">{{ $r->assignedDriverLabel() }}</td>
<td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.tms.requests.show', $r)])</td>
</tr>@empty<tr><td colspan="9" class="text-center py-8 text-gray-400">No records.</td></tr>@endforelse</tbody>
@elseif($tab === 'trips')
<thead><tr><th>ID</th><th>Employees</th><th>Pax</th><th>Vehicle</th><th>Driver</th><th>KM</th><th>Duty End</th><th>Driver Pay</th><th>Rental</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rows as $t)<tr>
<td class="tabular-nums">#{{ $t->id }}</td>
<td class="text-xs">{{ $t->transportRequests->pluck('employee.name')->filter()->implode(', ') }}</td>
<td>{{ $t->total_passengers }}</td><td class="text-xs">{{ $t->vehicle?->displayLabel() }}</td>
<td class="text-xs">{{ $t->assignedDriverLabel() }}</td>
<td class="tabular-nums">{{ $t->total_km ?? '—' }}</td>
<td>@include('partials.erp.datetime-highlight', ['at' => $t->duty_end_at, 'variant' => 'admin'])</td>
<td class="tabular-nums">৳{{ number_format($t->total_driver_pay ?: $t->ot_amount, 2) }}</td>
<td class="tabular-nums">@if($t->rental_charge_amount)৳{{ number_format($t->rental_charge_amount, 2) }}@else — @endif</td>
<td><span class="erp-badge {{ $t->tripStatusBadgeClass() }}">{{ $t->tripStatusLabel() }}</span></td>
<td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.tms.trips.show', $t)])</td>
</tr>@empty<tr><td colspan="11" class="text-center py-8 text-gray-400">No records.</td></tr>@endforelse</tbody>
@elseif($tab === 'fuel')
<thead><tr><th>Date</th><th>Vehicle</th><th>Type</th><th>Qty</th><th>Amount</th><th>Paid By</th></tr></thead>
<tbody>@forelse($rows as $f)<tr>
<td class="text-xs">{{ $f->created_at?->format('d M Y') }}</td><td class="text-xs">{{ $f->vehicle?->displayLabel() }}</td>
<td>{{ $f->fuel_type }}</td><td>{{ $f->quantity }}</td><td>৳{{ number_format($f->amount, 2) }}</td><td>{{ config('tms.fuel_paid_by.' . $f->paid_by, $f->paid_by) }}</td>
</tr>@empty<tr><td colspan="6" class="text-center py-8 text-gray-400">No records.</td></tr>@endforelse</tbody>
@elseif($tab === 'odometer')
<thead><tr><th>Date</th><th>Vehicle</th><th>Morning</th><th>Evening</th><th>Daily KM</th></tr></thead>
<tbody>@forelse($rows as $o)<tr>
<td>{{ $o->log_date?->format('d M Y') }}</td><td>{{ $o->vehicle?->displayLabel() }}</td>
<td>{{ $o->morning_km ?? '—' }}</td><td>{{ $o->evening_km ?? '—' }}</td><td>{{ $o->dailyKm() ?? '—' }}</td>
</tr>@empty<tr><td colspan="5" class="text-center py-8 text-gray-400">No records.</td></tr>@endforelse</tbody>
@elseif($tab === 'maintenance')
<thead><tr><th>Date</th><th>Vehicle</th><th>Type</th><th>Vendor</th><th>Labor</th><th>Parts</th><th>Total</th><th>Paid By</th><th>Status</th></tr></thead>
<tbody>@forelse($rows as $m)<tr>
<td class="text-xs">{{ $m->service_date?->format('d M Y') }}</td>
<td class="text-xs">{{ $m->vehicle?->displayLabel() }}</td>
<td>{{ $m->serviceTypeLabel() }}</td>
<td class="text-xs">{{ $m->vendor_name ?? '—' }}</td>
<td class="tabular-nums">৳{{ number_format($m->labor_cost, 2) }}</td>
<td class="tabular-nums">৳{{ number_format($m->parts_cost, 2) }}</td>
<td class="tabular-nums font-medium">৳{{ number_format($m->total_cost, 2) }}</td>
<td class="text-xs">{{ config('tms.fuel_paid_by.' . $m->paid_by, $m->paid_by) }}</td>
<td><span class="erp-badge {{ $m->statusBadgeClass() }}">{{ $m->statusLabel() }}</span></td>
</tr>@empty<tr><td colspan="9" class="text-center py-8 text-gray-400">No records.</td></tr>@endforelse</tbody>
@elseif($tab === 'rental_charges')
<thead><tr><th>Date</th><th>Vehicle</th><th>Vendor</th><th>KM</th><th>Rate</th><th>Amount</th><th>Status</th><th>Paid At</th><th></th></tr></thead>
<tbody>@forelse($rows as $c)<tr>
<td class="text-xs">{{ $c->log_date?->format('d M Y') ?? $c->created_at?->format('d M Y') }}</td>
<td class="text-xs">{{ $c->vehicle?->displayLabel() }}</td>
<td class="text-xs">{{ $c->rentalVendor?->name ?? '—' }}</td>
<td class="tabular-nums">{{ $c->total_km }}</td>
<td class="tabular-nums">৳{{ number_format($c->km_rate, 2) }}</td>
<td class="tabular-nums">৳{{ number_format($c->amount, 2) }}</td>
<td>{{ $c->payment_status }}</td>
<td>{{ $c->paid_at?->format('d M Y') ?? '—' }}</td>
<td class="text-right">@if($c->payment_status === 'pending' && auth()->user()->hasPermission('tms.rental_charges.manage'))
<form method="POST" action="{{ route('admin.tms.rental-charges.mark-paid', $c) }}" class="inline">@csrf<button type="submit" class="erp-btn-sm-primary" data-confirm="Mark as paid?">Mark Paid</button></form>
@endif</td>
</tr>@empty<tr><td colspan="9" class="text-center py-8 text-gray-400">No records.</td></tr>@endforelse</tbody>
@else
<thead><tr><th>Trip</th><th>Driver</th><th>Type</th><th>Night</th><th>Holiday</th><th>OT Hrs</th><th>OT Hourly</th><th>Total</th><th>Status</th><th>Paid At</th></tr></thead>
<tbody>@forelse($rows as $p)
@php $trip = $p->tripLog; $bd = $p->payment_breakdown ?? []; @endphp
<tr>
<td>#{{ $p->trip_log_id }}</td>
<td class="text-xs">{{ $trip?->assignedDriverLabel() ?? $p->driver?->displayLabel() ?? '—' }}</td>
<td class="text-xs">{{ $trip?->driver_type ?? '—' }}</td>
<td class="tabular-nums">৳{{ number_format($bd['night_bill_amount'] ?? $trip?->night_bill_amount ?? 0, 2) }}</td>
<td class="tabular-nums">৳{{ number_format($bd['holiday_duty_amount'] ?? $trip?->holiday_duty_amount ?? 0, 2) }}</td>
<td class="tabular-nums">{{ $bd['ot_hours'] ?? $trip?->ot_hours ?? '—' }}</td>
<td class="tabular-nums">৳{{ number_format($bd['ot_hourly_amount'] ?? $trip?->ot_hourly_amount ?? 0, 2) }}</td>
<td class="tabular-nums font-medium">৳{{ number_format($p->amount, 2) }}</td>
<td>{{ $p->payment_status }}</td>
<td>{{ $p->paid_at?->format('d M Y') ?? '—' }}</td>
</tr>@empty<tr><td colspan="10" class="text-center py-8 text-gray-400">No records.</td></tr>@endforelse</tbody>
@endif
</table>
@if($rows && $rows->hasPages())<div class="px-4 py-3 border-t">{{ $rows->links() }}</div>@endif
</div>
@endif
@endsection
