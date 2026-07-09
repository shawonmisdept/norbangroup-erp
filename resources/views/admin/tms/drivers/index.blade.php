@extends('layouts.admin')
@section('title', 'Drivers')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Drivers',
    'subtitle' => 'HRM employees registered as drivers',
    'actions' => auth()->user()->canManageTmsSubmodule('drivers')
        ? '<a href="' . route('admin.tms.drivers.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Driver</a>'
        : '',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="erp-filter-bar">
            @if($factories !== [])
                <div class="erp-filter-field">
                    <label class="erp-label">Unit</label>
                    <select name="factory_id" class="erp-input">
                        <option value="">All</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" @selected(($filters['factory_id'] ?? '') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="erp-filter-actions">
                <button type="submit" class="erp-btn-primary">Apply</button>
                <a href="{{ route('admin.tms.drivers.index') }}" class="erp-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <table class="erp-table tms-registry-table">
        <thead>
            <tr>
                <th>Unit</th>
                <th>Employee</th>
                <th>Assigned Vehicles</th>
                <th>License</th>
                <th class="text-right">OT Rate</th>
                <th class="text-center">OT Active</th>
                <th class="text-center">Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($drivers as $d)
                <tr>
                    <td class="text-xs align-top whitespace-nowrap">{{ $d->factory?->name }}</td>
                    <td class="align-top">
                        <span class="font-medium">{{ $d->employee?->name }}</span>

                    </td>
                    <td class="text-xs align-top min-w-[12rem]">
                        @if($d->vehicles->isNotEmpty())
                            <ul class="tms-vehicle-list">
                                @foreach($d->vehicles as $vehicle)
                                    <li>
                                        <span>{{ $vehicle->displayLabel() }}</span>
                                        @if($vehicle->pivot?->is_primary)
                                            <span class="text-gray-500">(primary)</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            {{ $d->defaultVehicle?->displayLabel() ?? '—' }}
                        @endif
                    </td>
                    <td class="text-xs align-top whitespace-nowrap">{{ $d->license_number ?? '—' }}</td>
                    <td class="tabular-nums text-right align-top whitespace-nowrap">৳{{ number_format($d->ot_rate, 2) }}</td>
                    <td class="text-center align-top">{{ $d->is_overtime_active ? 'Yes' : 'No' }}</td>
                    <td class="text-center align-top">
                        <span class="erp-badge {{ $d->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($d->status) }}
                        </span>
                    </td>
                    <td class="text-right align-top whitespace-nowrap">
                        @include('admin.tms.partials.row-actions', [
                            'viewUrl' => route('admin.tms.drivers.show', $d),
                            'editUrl' => auth()->user()->canManageTmsSubmodule('drivers') ? route('admin.tms.drivers.edit', $d) : null,
                            'destroyUrl' => auth()->user()->canManageTmsSubmodule('drivers') ? route('admin.tms.drivers.destroy', $d) : null,
                        ])
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center py-8 text-gray-400">No drivers yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($drivers->hasPages())
        <div class="px-4 py-3 border-t">{{ $drivers->links() }}</div>
    @endif
</div>
@endsection
