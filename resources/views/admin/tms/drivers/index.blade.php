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
        <a href="{{ route('admin.tms.drivers.index') }}" class="erp-btn-secondary">Reset</a>
    </div>
</form>

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Unit</th>
                <th>Employee</th>
                <th>Default Vehicle</th>
                <th>License</th>
                <th>OT Rate</th>
                <th>OT Active</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($drivers as $d)
                <tr>
                    <td class="text-xs">{{ $d->factory?->name }}</td>
                    <td>{{ $d->employee?->name }}</td>
                    <td class="text-xs">{{ $d->defaultVehicle?->displayLabel() ?? '—' }}</td>
                    <td class="text-xs">{{ $d->license_number ?? '—' }}</td>
                    <td class="tabular-nums">৳{{ number_format($d->ot_rate, 2) }}</td>
                    <td>{{ $d->is_overtime_active ? 'Yes' : 'No' }}</td>
                    <td>
                        <span class="erp-badge {{ $d->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($d->status) }}
                        </span>
                    </td>
                    <td class="text-right space-x-1">
                        <a href="{{ route('admin.tms.drivers.show', $d) }}" class="erp-btn-sm-secondary">View</a>
                        @if(auth()->user()->canManageTmsSubmodule('drivers'))
                            @include('admin.tms.partials.row-actions', [
                                'editUrl' => route('admin.tms.drivers.edit', $d),
                                'destroyUrl' => route('admin.tms.drivers.destroy', $d),
                            ])
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-8 text-gray-400">No drivers yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($drivers->hasPages())
        <div class="px-4 py-3 border-t">{{ $drivers->links() }}</div>
    @endif
</div>
@endsection
