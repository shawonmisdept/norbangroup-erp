@extends('layouts.admin')
@section('title', 'Maintenance')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Vehicle Maintenance', 'subtitle' => 'Service logs and parts', 'actions' => auth()->user()->canManageTmsSubmodule('maintenance') ? '<a href="' . route('admin.tms.maintenance.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Log</a>' : ''])
<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-4 gap-3 items-end">
@if($factories !== [])<div><label class="erp-label">Unit</label><select name="factory_id" class="erp-input"><option value="">All</option>@foreach($factories as $id => $name)<option value="{{ $id }}" @selected(($filters['factory_id'] ?? '') == $id)>{{ $name }}</option>@endforeach</select></div>@endif
<div><label class="erp-label">Vehicle</label><select name="vehicle_id" class="erp-input"><option value="">All</option>@foreach($vehicles as $id => $label)<option value="{{ $id }}" @selected(($filters['vehicle_id'] ?? '') == $id)>{{ $label }}</option>@endforeach</select></div>
<div><label class="erp-label">Status</label><select name="status" class="erp-input"><option value="">All</option>@foreach($statuses as $k => $l)<option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
<div><button type="submit" class="erp-btn-primary">Filter</button></div>
</form>
<div class="erp-panel overflow-hidden">
<table class="erp-table">
<thead><tr><th>Date</th><th>Vehicle</th><th>Type</th><th>Vendor</th><th>Labor</th><th>Parts</th><th>Total</th><th>Paid By</th><th>Status</th><th></th></tr></thead>
<tbody>
@forelse($logs as $log)
<tr>
<td class="text-xs">{{ $log->service_date?->format('d M Y') }}</td>
<td class="text-xs">{{ $log->vehicle?->displayLabel() }}</td>
<td>{{ $log->serviceTypeLabel() }}</td>
<td class="text-xs">{{ $log->vendor_name ?? '—' }}</td>
<td class="tabular-nums">৳{{ number_format($log->labor_cost, 2) }}</td>
<td class="tabular-nums">৳{{ number_format($log->parts_cost, 2) }}</td>
<td class="tabular-nums font-medium">৳{{ number_format($log->total_cost, 2) }}</td>
<td class="text-xs">{{ config('tms.fuel_paid_by.' . $log->paid_by, $log->paid_by) }}</td>
<td><span class="erp-badge {{ $log->statusBadgeClass() }}">{{ $log->statusLabel() }}</span></td>
<td class="text-right">@if(auth()->user()->canManageTmsSubmodule('maintenance'))@include('admin.tms.partials.row-actions', ['editUrl' => route('admin.tms.maintenance.edit', $log), 'destroyUrl' => route('admin.tms.maintenance.destroy', $log)])@endif</td>
</tr>
@empty<tr><td colspan="10" class="text-center py-8 text-gray-400">No maintenance logs yet.</td></tr>@endforelse
</tbody></table>
@if($logs->hasPages())<div class="px-4 py-3 border-t">{{ $logs->links() }}</div>@endif
</div>
@endsection
