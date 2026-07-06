@extends('layouts.admin')

@section('title', $period->periodLabel() . ' Attendance')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.attendance.periods') }}" class="hover:text-brand">Periods</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $period->periodLabel() }}</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'periods'])

@php
    $badge = match($period->status) {
        'draft' => 'bg-gray-100 text-gray-600',
        'processed' => 'bg-blue-100 text-blue-800',
        'frozen' => 'bg-green-100 text-green-800',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

@php
    $periodActions = '<span class="erp-badge ' . $badge . '">' . e($period->statusLabel()) . '</span>'
        . '<a href="' . route('admin.hrm.attendance.periods') . '" class="erp-btn-secondary">← Periods</a>';
    if (auth()->user()->canManageAttendanceSubmodule('periods') && ! $period->isFrozen()) {
        $periodActions .= '<form method="POST" action="' . route('admin.hrm.attendance.periods.freeze', $period) . '" class="inline" data-confirm="Freeze ' . e($period->periodLabel()) . '? This cannot be undone.">'
            . csrf_field()
            . '<button type="submit" class="erp-btn-primary !py-2 !px-4 text-xs">Freeze Period</button></form>';
    }
@endphp

@include('partials.erp.page-header', [
    'title' => $period->periodLabel() . ' Attendance',
    'subtitle' => ($period->factory?->name ?? '') . ' · ' . $period->start_date->format('d M') . ' – ' . $period->end_date->format('d M Y'),
    'actions' => $periodActions,
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="erp-form-label">Search employee</label>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or code…" class="erp-input !text-xs">
            </div>
            <button type="submit" class="erp-btn-secondary">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Employee Summary</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Present</th>
                    <th>Late</th>
                    <th>Late Accepted</th>
                    <th>Absent</th>
                    <th>Leave</th>
                    <th>Privilege</th>
                </tr>
            </thead>
            <tbody>
                @forelse($summaries as $row)
                    <tr>
                        <td>
                            <p class="font-medium text-sm">{{ $row->name }}</p>
                            <code class="text-[10px] text-gray-400">{{ $row->employee_code }}</code>
                        </td>
                        <td class="tabular-nums">{{ $row->present_count }}</td>
                        <td class="tabular-nums">{{ $row->late_count }}</td>
                        <td class="tabular-nums text-green-700">{{ $row->forgiven_count }}</td>
                        <td class="tabular-nums">{{ $row->absent_count }}</td>
                        <td class="tabular-nums">{{ $row->leave_count }}</td>
                        <td>
                            @if($row->late_acceptance_enabled)
                                <span class="erp-badge bg-blue-100 text-blue-800 text-[10px]">Standing</span>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-10 text-gray-400">No attendance logs for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($summaries->hasPages())
        <div class="px-4 py-3 border-t border-erp-border">{{ $summaries->links() }}</div>
    @endif
</div>
@endsection
