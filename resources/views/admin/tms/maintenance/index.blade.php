@extends('layouts.admin')
@section('title', 'Maintenance')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Vehicle Maintenance',
    'subtitle' => 'Select a vehicle to view or add maintenance bills',
    'actions' => '<a href="' . route('admin.tms.maintenance.posting') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Bill For Posting</a>',
])
<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-3 gap-3 items-end">
@if($factories !== [])<div><label class="erp-label">Unit</label><select name="factory_id" class="erp-input"><option value="">All</option>@foreach($factories as $id => $name)<option value="{{ $id }}" @selected(($filters['factory_id'] ?? '') == $id)>{{ $name }}</option>@endforeach</select></div>@endif
<div><button type="submit" class="erp-btn-primary">Filter</button></div>
</form>
<div class="erp-panel overflow-hidden">
<table class="erp-table">
<thead><tr><th>Vehicle</th><th>Car No (Posting)</th><th>Allocated User</th><th></th></tr></thead>
<tbody>
@forelse($vehicles as $vehicle)
<tr>
<td class="text-sm">{{ $vehicle->displayLabel() }}</td>
<td class="text-xs">{{ $vehicle->postingCarNoLabel() }}</td>
<td class="text-xs">{{ $vehicle->allocatedUserLabel() ?? '—' }}</td>
<td class="text-right">
<a href="{{ route('admin.tms.maintenance.register', $vehicle) }}" class="erp-btn-sm-secondary">Open Register</a>
</td>
</tr>
@empty
<tr><td colspan="4" class="text-center py-8 text-gray-400">No vehicles found.</td></tr>
@endforelse
</tbody>
</table>
@if($vehicles->hasPages())<div class="px-4 py-3 border-t">{{ $vehicles->links() }}</div>@endif
</div>
@endsection
