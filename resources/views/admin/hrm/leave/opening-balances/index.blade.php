@extends('layouts.admin')

@section('title', 'Leave Balances — ' . config('app.name'))

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.leave.hub') }}" class="hover:text-brand">Leave</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Balances</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Leave Balances',
    'subtitle' => 'Entitlement, used, and available days per employee',
    'actions' => '<div class="flex flex-wrap gap-2">'
        . ($canManage ? '<a href="' . route('admin.hrm.leave.opening-balances.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Opening Balance</a>' : '')
        . '<a href="' . route('admin.hrm.leave.hub') . '" class="erp-btn-secondary">← Hub</a>'
        . '<a href="' . route('admin.hrm.leave.transactions.index') . '" class="erp-btn-secondary">Applications</a>'
        . '</div>',
])

@include('admin.hrm.partials.submodule-nav', ['section' => 'leave', 'current' => 'opening-balances'])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.leave.opening-balances.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-24">
                <label class="erp-form-label">Year</label>
                <input type="number" name="year" value="{{ $year }}" min="2020" max="2099" class="erp-input !text-xs">
            </div>
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
            <div class="w-44">
                <label class="erp-form-label">Leave Type</label>
                <select name="leave_type_id" class="erp-input !text-xs">
                    <option value="">All types</option>
                    @foreach($leaveTypes as $id => $name)
                        <option value="{{ $id }}" {{ (string) ($filters['leave_type_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Leave Type</th>
                <th>Entitled</th>
                <th>Used</th>
                <th>Pending</th>
                <th>Available</th>
                @if($canManage)<th></th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse($balances as $balance)
                <tr>
                    <td>
                        <p class="font-medium text-sm">{{ $balance->employee->name }}</p>
                        <code class="text-[10px] text-gray-400">{{ $balance->employee->employee_code }}</code>
                    </td>
                    <td class="text-sm">{{ $balance->leaveType->name }}</td>
                    <td class="text-sm tabular-nums">{{ number_format($balance->entitled_days, 1) }}</td>
                    <td class="text-sm tabular-nums">{{ number_format($balance->used_days, 1) }}</td>
                    <td class="text-sm tabular-nums">{{ number_format($balance->pending_days, 1) }}</td>
                    <td class="text-sm tabular-nums font-medium">{{ number_format($balance->availableDays(), 1) }}</td>
                    @if($canManage)
                    <td class="text-right">
                        @include('partials.erp.table-actions', [
                            'editUrl' => route('admin.hrm.leave.opening-balances.edit', $balance),
                        ])
                    </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canManage ? 7 : 6 }}" class="text-center text-gray-400 py-8 text-sm">No balance records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if($balances->hasPages())
        <div class="px-4 py-3 border-t border-erp-border">{{ $balances->links() }}</div>
    @endif
</div>
@endsection
