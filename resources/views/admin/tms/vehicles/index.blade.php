@extends('layouts.admin')
@section('title', 'Vehicles')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Vehicles',
    'subtitle' => 'Own and rental vehicle fleet',
    'actions' => auth()->user()->canManageTmsSubmodule('vehicles')
        ? '<a href="' . route('admin.tms.vehicles.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Vehicle</a>'
        : '',
])

<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
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
        <label class="erp-label">Status</label>
        <select name="status" class="erp-input">
            <option value="">All</option>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="erp-btn-primary">Apply</button>
        <a href="{{ route('admin.tms.vehicles.index') }}" class="erp-btn-secondary">Reset</a>
    </div>
</form>

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Unit</th>
                <th>Name</th>
                <th>Reg</th>
                <th>Type</th>
                <th>Capacity</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($vehicles as $v)
                <tr>
                    <td class="text-xs">{{ $v->factory?->name }}</td>
                    <td>{{ $v->name }}</td>
                    <td class="tabular-nums text-xs">{{ $v->reg_number }}</td>
                    <td class="text-xs capitalize">{{ $v->type }}</td>
                    <td class="tabular-nums">{{ $v->passenger_capacity }}</td>
                    <td><span class="erp-badge {{ $v->statusBadgeClass() }}">{{ $v->statusLabel() }}</span></td>
                    <td class="text-right space-x-1">
                        <a href="{{ route('admin.tms.vehicles.show', $v) }}" class="erp-btn-sm-secondary">View</a>
                        @if(auth()->user()->canManageTmsSubmodule('vehicles'))
                            @include('admin.tms.partials.row-actions', [
                                'editUrl' => route('admin.tms.vehicles.edit', $v),
                                'destroyUrl' => route('admin.tms.vehicles.destroy', $v),
                            ])
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-8 text-gray-400">No vehicles yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($vehicles->hasPages())
        <div class="px-4 py-3 border-t">{{ $vehicles->links() }}</div>
    @endif
</div>
@endsection
