@extends('layouts.admin')

@section('title', 'Attendance Dashboard')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Attendance Dashboard</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Attendance Dashboard',
    'subtitle' => $period_label . ' · Snapshot: ' . $snapshot_date,
    'actions' => '<a href="' . route('admin.hrm.attendance.hub') . '" class="erp-btn-secondary">Hub</a> <a href="' . route('admin.hrm.attendance.daily') . '" class="erp-btn-secondary">Daily Summary</a>',
])

@include('admin.hrm.partials.dashboard-filters', ['routeName' => 'admin.hrm.attendance.dashboard'])
@include('admin.hrm.partials.dashboard-kpis', ['kpis' => $kpis, 'columns' => 'grid-cols-2 md:grid-cols-3 xl:grid-cols-6'])

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="xl:col-span-2 erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Today by Department</h2></div>
        <div class="overflow-x-auto">
            <table class="erp-table text-sm">
                <thead><tr><th>Department</th><th>Present</th><th>Absent</th></tr></thead>
                <tbody>
                    @forelse($today_departments as $row)
                        <tr>
                            <td>{{ $row->department_name }}</td>
                            <td>{{ $row->present_count }}</td>
                            <td>{{ $row->absent_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-gray-400 py-6">No attendance processed for snapshot date.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Pending Late Acceptance</h2></div>
            <div class="erp-panel-body space-y-2">
                @forelse($pending_late_acceptance as $row)
                    <a href="{{ route('admin.hrm.attendance.late-acceptance.show', $row) }}" class="block border border-erp-border rounded-sm p-2 hover:border-brand/40 text-sm">
                        <p class="font-medium">{{ $row->employee?->name }}</p>
                        <p class="text-xs text-gray-500">{{ optional($row->dailyLog?->attendance_date)->format('d M Y') ?? '—' }}</p>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">No pending late acceptance requests.</p>
                @endforelse
            </div>
        </div>
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Open Periods</h2></div>
            <div class="erp-panel-body space-y-2">
                @forelse($open_periods as $row)
                    <a href="{{ route('admin.hrm.attendance.periods.show', $row) }}" class="block border border-erp-border rounded-sm p-2 hover:border-brand/40 text-sm">
                        <p class="font-medium">{{ $row->month }}/{{ $row->year }}</p>
                        <p class="text-xs text-gray-500">{{ $row->statusLabel() }}</p>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">No open attendance periods.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
