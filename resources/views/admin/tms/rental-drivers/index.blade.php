@extends('layouts.admin')
@section('title', 'Rental Drivers')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Rental Drivers', 'subtitle' => 'External drivers — not linked to HR/Payroll', 'actions' => auth()->user()->canManageTmsSubmodule('rental_drivers') ? '<a href="' . route('admin.tms.rental-drivers.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Rental Driver</a>' : ''])
<div class="erp-panel overflow-hidden">
<table class="erp-table">
<thead><tr><th>Unit</th><th>Name</th><th>Mobile</th><th>License</th><th>Vendor</th><th>Default Vehicle</th><th>Status</th><th></th></tr></thead>
<tbody>
@forelse($drivers as $driver)
<tr>
<td class="text-xs">{{ $driver->factory?->name }}</td>
<td>{{ $driver->name }}</td>
<td class="text-xs">{{ $driver->mobile ?? '—' }}</td>
<td class="text-xs">{{ $driver->license_number ?? '—' }}</td>
<td class="text-xs">{{ $driver->vendorLabel() }}</td>
<td class="text-xs">{{ $driver->defaultVehicle?->displayLabel() ?? '—' }}</td>
<td><span class="erp-badge {{ $driver->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ ucfirst($driver->status) }}</span></td>
<td class="text-right">@if(auth()->user()->canManageTmsSubmodule('rental_drivers'))@include('admin.tms.partials.row-actions', ['editUrl' => route('admin.tms.rental-drivers.edit', $driver), 'destroyUrl' => route('admin.tms.rental-drivers.destroy', $driver)])@endif</td>
</tr>
@empty<tr><td colspan="8" class="text-center py-8 text-gray-400">No rental drivers yet.</td></tr>@endforelse
</tbody></table>
@if($drivers->hasPages())<div class="px-4 py-3 border-t">{{ $drivers->links() }}</div>@endif
</div>
@endsection
