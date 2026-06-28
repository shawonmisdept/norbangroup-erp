@extends('layouts.admin')
@section('title', 'Daily Odometer')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Daily KM Log', 'subtitle' => 'Morning and evening odometer — entered by admin, not driver', 'actions' => auth()->user()->canManageTmsSubmodule('odometer') ? '<a href="' . route('admin.tms.odometer.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Log</a>' : ''])
<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
<div><label class="erp-label">From</label><input type="date" name="from" class="erp-input" value="{{ $filters['from'] ?? '' }}"></div>
<div><label class="erp-label">To</label><input type="date" name="to" class="erp-input" value="{{ $filters['to'] ?? '' }}"></div>
<div><label class="erp-label">Vehicle</label>
<select name="vehicle_id" class="erp-input"><option value="">All</option>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected(($filters['vehicle_id'] ?? '') == $v->id)>{{ $v->displayLabel() }}</option>@endforeach</select></div>
<button type="submit" class="erp-btn-primary">Filter</button>
</form>
<div class="erp-panel overflow-hidden">
<table class="erp-table">
<thead><tr><th>Date</th><th>Vehicle</th><th>Driver</th><th>Morning KM</th><th>Evening KM</th><th>Daily KM</th><th></th></tr></thead>
<tbody>
@forelse($logs as $log)
<tr>
<td class="tabular-nums text-xs">{{ $log->log_date?->format('d M Y') }}</td>
<td class="text-xs">{{ $log->vehicle?->displayLabel() }}</td>
<td class="text-xs">{{ $log->vehicle?->assignedDriverNames() ?? '—' }}</td>
<td class="tabular-nums">{{ $log->morning_km ?? '—' }}</td>
<td class="tabular-nums">{{ $log->evening_km ?? '—' }}</td>
<td class="tabular-nums font-medium">{{ $log->dailyKm() ?? '—' }}</td>
<td class="text-right">@if(auth()->user()->canManageTmsSubmodule('odometer'))@include('admin.tms.partials.row-actions', ['editUrl' => route('admin.tms.odometer.edit', $log)])@endif</td>
</tr>
@empty<tr><td colspan="7" class="text-center py-8 text-gray-400">No odometer logs yet.</td></tr>@endforelse
</tbody></table>
@if($logs->hasPages())<div class="px-4 py-3 border-t">{{ $logs->links() }}</div>@endif
</div>
@endsection
