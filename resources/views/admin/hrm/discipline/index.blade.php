@extends('layouts.admin')

@section('title', 'Disciplinary Records')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Disciplinary</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Disciplinary Records',
    'subtitle' => 'Warnings, suspensions & misconduct log',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.discipline.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">New Record</a>' : ''),
])

<div class="grid grid-cols-1 gap-3 mb-4 max-w-xs">
    <div class="erp-panel"><div class="erp-panel-body"><p class="text-xl font-bold text-amber-600">{{ $stats['open'] }}</p><p class="text-xs text-gray-500 uppercase">Open Records</p></div></div>
</div>

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[180px]">
                <label class="erp-form-label">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Employee ID, name…" class="erp-input !text-xs">
            </div>
            @if(count($factories) > 1)
                <div class="w-44">
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" class="erp-input !text-xs">
                        <option value="">All units</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ (string) ($filters['factory_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="w-36">
                <label class="erp-form-label">Status</label>
                <select name="status" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-44">
                <label class="erp-form-label">Action Type</label>
                <select name="action_type" class="erp-input !text-xs">
                    <option value="">All types</option>
                    @foreach($actionTypes as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['action_type'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary !py-2 !px-4 text-xs">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel">
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Incident</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    @php
                        $badge = $record->status === 'open' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600';
                    @endphp
                    <tr>
                        <td>
                            <p class="font-medium text-sm">{{ $record->employee->name }}</p>
                            <code class="text-xs text-gray-500">{{ $record->employee->employee_code }}</code>
                        </td>
                        <td>{{ $record->typeLabel() }}</td>
                        <td class="text-xs text-gray-600">{{ $record->incident_date->format('d M Y') }}</td>
                        <td><span class="erp-badge {{ $badge }}">{{ $record->statusLabel() }}</span></td>
                        <td class="text-right">
                            <a href="{{ route('admin.hrm.discipline.show', $record) }}" class="erp-btn-sm-secondary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-gray-400 py-8">No disciplinary records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
        <div class="erp-panel-body border-t border-erp-border">{{ $records->links() }}</div>
    @endif
</div>
@endsection
