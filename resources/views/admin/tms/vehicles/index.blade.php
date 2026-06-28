@extends('layouts.admin')
@section('title', 'Vehicles')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Vehicles', 'subtitle' => 'Own and rental vehicle fleet', 'actions' => auth()->user()->canManageTmsSubmodule('vehicles') ? '<a href="' . route('admin.tms.vehicles.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Vehicle</a>' : ''])
<div class="erp-panel overflow-hidden">
<table class="erp-table">
<thead><tr><th>Unit</th><th>Name</th><th>Reg</th><th>Type</th><th>Capacity</th><th>Status</th><th></th></tr></thead>
<tbody>
@forelse($vehicles as $v)
<tr>
<td class="text-xs">{{ $v->factory?->name }}</td>
<td>{{ $v->name }}</td>
<td class="tabular-nums text-xs">{{ $v->reg_number }}</td>
<td class="text-xs capitalize">{{ $v->type }}</td>
<td class="tabular-nums">{{ $v->passenger_capacity }}</td>
<td><span class="erp-badge {{ $v->statusBadgeClass() }}">{{ $v->statusLabel() }}</span></td>
<td class="text-right">@if(auth()->user()->canManageTmsSubmodule('vehicles'))@include('admin.tms.partials.row-actions', ['editUrl' => route('admin.tms.vehicles.edit', $v), 'destroyUrl' => route('admin.tms.vehicles.destroy', $v)])@endif</td>
</tr>
@empty<tr><td colspan="7" class="text-center py-8 text-gray-400">No vehicles yet.</td></tr>@endforelse
</tbody></table>
@if($vehicles->hasPages())<div class="px-4 py-3 border-t">{{ $vehicles->links() }}</div>@endif
</div>
@endsection
