@extends('layouts.admin')

@section('title', 'Late Acceptance')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Late Acceptance</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'late-acceptance'])

@include('partials.erp.page-header', [
    'title' => 'Late Acceptance Applications',
    'subtitle' => 'Employee requests to forgive late days — approved applications skip salary deduction',
])

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
    <div class="erp-panel">
        <div class="erp-panel-body">
            <p class="text-2xl font-bold text-amber-600">{{ $stats['pending'] }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Pending</p>
        </div>
    </div>
</div>

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[180px]">
                <label class="erp-form-label">Search</label>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or code…" class="erp-input !text-xs">
            </div>
            <div class="w-36">
                <label class="erp-form-label">Status</label>
                <select name="status" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Applied</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                    @php
                        $badge = match($application->status) {
                            'pending' => 'bg-amber-100 text-amber-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <tr>
                        <td>
                            <p class="font-medium text-sm">{{ $application->employee->name }}</p>
                            <code class="text-[10px] text-gray-400">{{ $application->employee->employee_code }}</code>
                        </td>
                        <td class="text-sm tabular-nums">{{ $application->attendance_date->format('d M Y') }}</td>
                        <td class="text-xs text-gray-600 max-w-xs truncate">{{ $application->reason ?? '—' }}</td>
                        <td><span class="erp-badge {{ $badge }}">{{ $application->statusLabel() }}</span></td>
                        <td class="text-xs text-gray-500">@portalDateTime($application->applied_at)</td>
                        <td class="text-right">
                            @include('partials.erp.table-actions', [
                                'viewUrl' => route('admin.hrm.attendance.late-acceptance.show', $application),
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-10 text-gray-400">No applications yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($applications->hasPages())
        <div class="px-4 py-3 border-t border-erp-border">{{ $applications->links() }}</div>
    @endif
</div>
@endsection
