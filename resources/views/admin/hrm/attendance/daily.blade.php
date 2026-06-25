@extends('layouts.admin')

@section('title', 'Daily Attendance — ' . config('app.name'))

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Daily Summary</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'daily'])

@include('partials.erp.page-header', [
    'title' => 'Daily Attendance Summary',
    'subtitle' => 'Processed IN/OUT, late minutes, and late acceptance status',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.attendance.daily') }}" class="flex flex-wrap items-end gap-3">
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
            <div class="w-36">
                <label class="erp-form-label">From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="erp-input !text-xs">
            </div>
            <div class="w-36">
                <label class="erp-form-label">To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="erp-input !text-xs">
            </div>
            <button type="submit" class="erp-btn-secondary">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Daily Logs</h2>
        <span class="text-[11px] text-gray-400">{{ $logs->total() }} record(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Work</th>
                    <th>Late</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $isLateForgiven = $log->status === 'late' && (
                            $log->is_late_forgiven
                            || $log->employee?->late_acceptance_enabled
                            || $log->lateAcceptanceApplication?->isApproved()
                        );
                        $isLatePending = $log->status === 'late' && $log->lateAcceptanceApplication?->isPending();
                        $badge = match(true) {
                            $isLateForgiven => 'bg-green-100 text-green-800',
                            $isLatePending => 'bg-blue-100 text-blue-800',
                            $log->status === 'present' => 'bg-green-100 text-green-800',
                            $log->status === 'late' => 'bg-amber-100 text-amber-800',
                            $log->status === 'absent' => 'bg-red-100 text-red-800',
                            $log->status === 'half_day' => 'bg-orange-100 text-orange-800',
                            $log->status === 'holiday' => 'bg-blue-100 text-blue-800',
                            $log->status === 'off_day' => 'bg-gray-100 text-gray-600',
                            default => 'bg-gray-100 text-gray-600',
                        };
                        $statusLabel = $log->displayStatusLabel();
                    @endphp
                    <tr>
                        <td class="text-xs tabular-nums">{{ $log->attendance_date->format('d M Y') }}</td>
                        <td>
                            <p class="font-medium text-sm">{{ $log->employee?->name }}</p>
                            <p class="text-[11px] text-gray-400 font-mono">{{ $log->employee?->employee_code }}</p>
                        </td>
                        <td class="text-xs tabular-nums">{{ $log->check_in?->format('H:i') ?? '—' }}</td>
                        <td class="text-xs tabular-nums">{{ $log->check_out?->format('H:i') ?? '—' }}</td>
                        <td class="text-xs tabular-nums">{{ $log->workHoursFormatted() }}</td>
                        <td class="text-xs tabular-nums">{{ $log->late_minutes > 0 ? $log->late_minutes . 'm' : '—' }}</td>
                        <td><span class="erp-badge {{ $badge }}">{{ $statusLabel }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-10 text-gray-400">No daily logs yet. Process attendance from Periods.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="px-4 py-3 border-t border-erp-border bg-gray-50/50">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
