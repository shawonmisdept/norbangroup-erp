@extends('layouts.admin')

@section('title', 'Employee Separations')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Separations</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Employee Separations',
    'subtitle' => 'Resignation, termination & exit workflow',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.separations.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs mr-2">New Separation</a>' : '')
        . '<a href="' . route('admin.hrm.separations.export', $filters) . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Export CSV</a>',
])

<div class="grid grid-cols-2 gap-3 mb-4 max-w-md">
    <div class="erp-panel"><div class="erp-panel-body"><p class="text-xl font-bold text-amber-600">{{ $stats['pending_hr'] }}</p><p class="text-xs text-gray-500 uppercase">Awaiting HR</p></div></div>
    <div class="erp-panel"><div class="erp-panel-body"><p class="text-xl font-bold text-orange-600">{{ $stats['pending_reporting'] }}</p><p class="text-xs text-gray-500 uppercase">Awaiting Reporting</p></div></div>
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
            <div class="w-40">
                <label class="erp-form-label">Type</label>
                <select name="separation_type" class="erp-input !text-xs">
                    <option value="">All types</option>
                    @foreach($separationTypes as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['separation_type'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
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
                    <th>Last Day</th>
                    <th>Status</th>
                    <th>Applied</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($separations as $row)
                    @php
                        $badge = match($row->status) {
                            'pending' => 'bg-amber-100 text-amber-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <tr>
                        <td>
                            <p class="font-medium">{{ $row->employee?->name }}</p>
                            <code class="text-[10px] text-gray-400">{{ $row->employee?->employee_code }}</code>
                        </td>
                        <td>{{ $row->typeLabel() }}</td>
                        <td class="tabular-nums">{{ $row->last_working_day->format('d M Y') }}</td>
                        <td>
                            <span class="erp-badge {{ $badge }}">{{ $row->statusLabel() }}</span>
                            @if($row->isPending() && $row->pendingStepLabel())
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $row->pendingStepLabel() }}</p>
                            @endif
                        </td>
                        <td class="tabular-nums text-xs text-gray-500">{{ $row->applied_at?->format('d M Y') }}</td>
                        <td>@include('partials.erp.table-actions', ['viewUrl' => route('admin.hrm.separations.show', $row)])</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-8 text-gray-400">No separation requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($separations->hasPages())
        <div class="erp-panel-footer">{{ $separations->links() }}</div>
    @endif
</div>
@endsection
