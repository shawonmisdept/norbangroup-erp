@extends('layouts.admin')
@section('title', 'Vehicles')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Vehicles',
    'subtitle' => 'Fleet register — own & rental vehicles with papers and assignment',
    'actions' => collect([
        auth()->user()->canViewTmsSubmodule('vehicles')
            ? '<a href="' . route('admin.tms.vehicles.papers') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Papers Status</a>'
            : null,
        auth()->user()->canManageTmsSubmodule('vehicles')
            ? '<a href="' . route('admin.tms.vehicles.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Vehicle</a>'
            : null,
    ])->filter()->implode(' '),
])

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500">Total Vehicles</p>
        <p class="text-2xl font-bold tabular-nums">{{ $stats['total'] }}</p>
    </div>
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500">Available</p>
        <p class="text-2xl font-bold tabular-nums text-green-700">{{ $stats['available'] }}</p>
    </div>
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500">In Maintenance</p>
        <p class="text-2xl font-bold tabular-nums text-amber-700">{{ $stats['maintenance'] }}</p>
    </div>
    <a href="{{ route('admin.tms.vehicles.papers', ['paper_status' => 'expired']) }}" class="erp-panel p-4 hover:bg-red-50 transition {{ $stats['papers'] ? 'ring-1 ring-red-200' : '' }}">
        <p class="text-xs text-gray-500">Paper Alerts</p>
        <p class="text-2xl font-bold tabular-nums text-red-700">{{ $stats['papers'] }}</p>
    </a>
</div>

<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 items-end">
    <div class="xl:col-span-2">
        <label class="erp-label">Search</label>
        <input type="text" name="search" class="erp-input" value="{{ $filters['search'] ?? '' }}" placeholder="Name or registration no…">
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
    <div>
        <label class="erp-label">Fleet Type</label>
        <select name="type" class="erp-input">
            <option value="">All</option>
            @foreach($types as $value => $label)
                <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="erp-label">Status</label>
        <select name="status" class="erp-input">
            <option value="">All</option>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="erp-label">Papers</label>
        <select name="paper_status" class="erp-input">
            <option value="">All</option>
            @foreach($paperStatuses as $value => $label)
                <option value="{{ $value }}" @selected(($filters['paper_status'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="erp-btn-primary flex-1">Filter</button>
        @if(array_filter($filters ?? []))
            <a href="{{ route('admin.tms.vehicles.index') }}" class="erp-btn-secondary">Clear</a>
        @endif
    </div>
</form>

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Unit</th>
                <th>Type</th>
                <th>Allocated User</th>
                <th>Driver</th>
                <th>Papers</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vehicles as $v)
                @php $paperStatus = $paperService->worstStatusForVehicle($v); @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.tms.vehicles.show', $v) }}" class="font-medium text-indigo-600 hover:underline">{{ $v->name }}</a>
                        <p class="text-xs text-gray-500 tabular-nums mt-0.5">{{ $v->reg_number }}</p>
                        @if($v->model_year || $v->vehicle_category)
                            <p class="text-[10px] text-gray-400 mt-0.5">
                                {{ config('tms.vehicle_categories.' . $v->vehicle_category, '') }}
                                @if($v->model_year)· {{ $v->model_year }}@endif
                                · {{ $v->passenger_capacity }} seats
                            </p>
                        @endif
                    </td>
                    <td class="text-xs whitespace-nowrap">{{ $v->factory?->name }}</td>
                    <td class="text-xs">
                        <span class="capitalize">{{ $v->type }}</span>
                        @if($v->is_dedicated)
                            <span class="erp-badge bg-indigo-50 text-indigo-700 text-[10px] ml-1">Dedicated</span>
                        @endif
                    </td>
                    <td class="text-xs max-w-[140px] truncate" title="{{ $v->allocatedUserLabel() }}">{{ $v->allocatedUserLabel() ?? '—' }}</td>
                    <td class="text-xs whitespace-nowrap">{{ $v->assignedDriverNames() }}</td>
                    <td>
                        <span class="erp-badge {{ $paperService->statusBadgeClass($paperStatus) }}">{{ ucfirst($paperStatus) }}</span>
                    </td>
                    <td><span class="erp-badge {{ $v->statusBadgeClass() }}">{{ $v->statusLabel() }}</span></td>
                    <td class="text-right">
                        @include('partials.erp.table-actions', [
                            'viewUrl' => route('admin.tms.vehicles.show', $v),
                            'editUrl' => auth()->user()->canManageTmsSubmodule('vehicles') ? route('admin.tms.vehicles.edit', $v) : null,
                            'destroyUrl' => auth()->user()->canManageTmsSubmodule('vehicles') ? route('admin.tms.vehicles.destroy', $v) : null,
                            'destroyConfirm' => 'Delete vehicle ' . $v->reg_number . '?',
                        ])
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-10 text-gray-400">
                        <p class="mb-3">No vehicles found.</p>
                        @if(auth()->user()->canManageTmsSubmodule('vehicles'))
                            <a href="{{ route('admin.tms.vehicles.create') }}" class="erp-btn-primary !py-2 !px-4 text-xs">Add First Vehicle</a>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($paginator && $paginator->hasPages())
        <div class="px-4 py-3 border-t">{{ $paginator->links() }}</div>
    @endif
</div>
@endsection
