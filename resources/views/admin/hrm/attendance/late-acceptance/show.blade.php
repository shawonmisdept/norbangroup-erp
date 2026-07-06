@extends('layouts.admin')

@section('title', 'Late Acceptance #' . $application->id)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.attendance.late-acceptance.index') }}" class="hover:text-brand">Late Acceptance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">#{{ $application->id }}</span>
@endsection

@section('admin-content')
@php
    $badge = match($application->status) {
        'pending' => 'bg-amber-100 text-amber-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

@include('partials.erp.page-header', [
    'title' => 'Late Acceptance #' . $application->id,
    'subtitle' => $application->employee->name . ' · ' . $application->attendance_date->format('d M Y'),
    'actions' => '<a href="' . route('admin.hrm.attendance.late-acceptance.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 erp-panel">
        <div class="erp-panel-head">
            <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Application Details</h2>
        </div>
        <div class="erp-panel-body grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-[10px] text-gray-400 uppercase">Employee</p>
                <p class="font-medium">{{ $application->employee->name }}</p>
                <code class="text-xs text-gray-500">{{ $application->employee->employee_code }}</code>
            </div>
            <div>
                <p class="text-[10px] text-gray-400 uppercase">Late Date</p>
                <p class="tabular-nums">{{ $application->attendance_date->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-[10px] text-gray-400 uppercase">Status</p>
                <span class="erp-badge {{ $badge }}">{{ $application->statusLabel() }}</span>
            </div>
            <div>
                <p class="text-[10px] text-gray-400 uppercase">Applied</p>
                <p>@portalDateTime($application->applied_at)</p>
            </div>
            <div class="col-span-2">
                <p class="text-[10px] text-gray-400 uppercase">Reason</p>
                <p class="text-gray-700">{{ $application->reason ?? '—' }}</p>
            </div>
            @if($application->rejection_reason)
                <div class="col-span-2">
                    <p class="text-[10px] text-gray-400 uppercase">Rejection Reason</p>
                    <p class="text-red-700">{{ $application->rejection_reason }}</p>
                </div>
            @endif
        </div>
    </div>

    @if($application->isPending() && auth()->user()->hasPermission('hrm.attendance.approve'))
        <div class="erp-panel">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">HR Action</h2>
            </div>
            <div class="erp-panel-body space-y-4">
                <form method="POST" action="{{ route('admin.hrm.attendance.late-acceptance.approve', $application) }}"
                      data-confirm="Approve late acceptance? Salary will not be deducted for this day."
                      data-confirm-variant="primary"
                      data-confirm-ok="Yes, approve">
                    @csrf
                    <button type="submit" class="erp-btn-primary w-full justify-center">Approve</button>
                </form>
                <form method="POST" action="{{ route('admin.hrm.attendance.late-acceptance.reject', $application) }}" class="space-y-2">
                    @csrf
                    <label class="erp-form-label">Rejection reason</label>
                    <textarea name="rejection_reason" rows="3" required class="erp-input !text-xs" placeholder="Reason for rejection…"></textarea>
                    <button type="submit" class="erp-btn-secondary w-full justify-center text-red-700 border-red-200">Reject</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
