@extends('layouts.admin')
@section('title', 'Rental Drivers')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Rental Drivers',
    'subtitle' => 'External drivers — not linked to HR/Payroll',
    'actions' => auth()->user()->canManageTmsSubmodule('rental_drivers')
        ? '<a href="' . route('admin.tms.rental-drivers.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Rental Driver</a>'
        : '',
])

<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-4 gap-3 items-end">
    @if($factories !== [])
        <div>
            <label class="erp-label">Unit</label>
            <select name="factory_id" class="erp-input">
                <option value="">All</option>
                @foreach($factories as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['factory_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="flex gap-2">
        <button type="submit" class="erp-btn-primary">Apply</button>
        <a href="{{ route('admin.tms.rental-drivers.index') }}" class="erp-btn-secondary">Reset</a>
    </div>
</form>

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th class="w-[48px]"></th>
                <th>Unit</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>License</th>
                <th>Vendor</th>
                <th>Default Vehicle</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($drivers as $driver)
                <tr>
                    <td>
                        @if($driver->photoUrl())
                            <img src="{{ $driver->photoUrl() }}" alt="{{ $driver->name }}" class="erp-rental-driver-photo">
                        @else
                            <div class="erp-rental-driver-photo-fallback">{{ $driver->initials() }}</div>
                        @endif
                    </td>
                    <td class="text-xs">{{ $driver->factory?->name }}</td>
                    <td>{{ $driver->name }}</td>
                    <td class="text-xs">{{ $driver->mobile ?? '—' }}</td>
                    <td class="text-xs">{{ $driver->license_number ?? '—' }}</td>
                    <td class="text-xs">{{ $driver->vendorLabel() }}</td>
                    <td class="text-xs">{{ $driver->defaultVehicle?->displayLabel() ?? '—' }}</td>
                    <td>
                        <span class="erp-badge {{ $driver->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($driver->status) }}
                        </span>
                    </td>
                    <td class="text-right">
                        @include('admin.tms.partials.row-actions', [
                            'viewModalUrl' => route('admin.tms.rental-drivers.show', $driver),
                            'editUrl' => auth()->user()->canManageTmsSubmodule('rental_drivers')
                                ? route('admin.tms.rental-drivers.edit', $driver)
                                : null,
                            'destroyUrl' => auth()->user()->canManageTmsSubmodule('rental_drivers')
                                ? route('admin.tms.rental-drivers.destroy', $driver)
                                : null,
                        ])
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center py-8 text-gray-400">No rental drivers yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($drivers->hasPages())
        <div class="px-4 py-3 border-t">{{ $drivers->links() }}</div>
    @endif
</div>

@include('admin.tms.partials.view-modal')
@endsection
