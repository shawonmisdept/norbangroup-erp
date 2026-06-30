@extends('layouts.admin')
@section('title', 'Maintenance')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Vehicle Maintenance',
    'subtitle' => 'Select a vehicle to view or add maintenance bills',
    'actions' => '<a href="' . route('admin.tms.maintenance.posting') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Bill For Posting</a>',
])

<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 items-end">
    <div>
        <label class="erp-label">Search</label>
        <input type="text" name="search" class="erp-input" value="{{ $filters['search'] ?? '' }}" placeholder="Search all…">
    </div>

    <div>
        <label class="erp-label">Vehicle</label>
        <select name="vehicle_id" class="erp-input">
            <option value="">All</option>
            @foreach($vehicleOptions as $id => $label)
                <option value="{{ $id }}" @selected(($filters['vehicle_id'] ?? '') == $id)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="erp-label">Car No (Posting)</label>
        <select name="posting_vehicle_id" class="erp-input">
            <option value="">All</option>
            @foreach($postingCarOptions as $id => $label)
                <option value="{{ $id }}" @selected(($filters['posting_vehicle_id'] ?? '') == $id)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="erp-label">Allocated User</label>
        <select name="allocated_employee_id" class="erp-input">
            <option value="">All</option>
            @foreach($allocatedUserOptions as $id => $label)
                <option value="{{ $id }}" @selected(($filters['allocated_employee_id'] ?? '') == $id)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

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
        <button type="submit" class="erp-btn-primary flex-1">Filter</button>
        @if(array_filter($filters ?? []))
            <a href="{{ route('admin.tms.maintenance.index') }}" class="erp-btn-secondary">Clear</a>
        @endif
    </div>
</form>

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Car No (Posting)</th>
                <th>Allocated User</th>
                <th></th>
            </tr>
        </thead>
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

    @if($vehicles->hasPages())
        <div class="px-4 py-3 border-t">{{ $vehicles->links() }}</div>
    @endif
</div>
@endsection
