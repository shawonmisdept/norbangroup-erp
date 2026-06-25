@extends('layouts.admin')

@section('title', 'Leave Application — ' . config('app.name'))

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.leave.hub') }}" class="hover:text-brand">Leave</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.leave.transactions.index') }}" class="hover:text-brand">Applications</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">#{{ $application->id }}</span>
@endsection

@section('admin-content')
@php
    $badge = match($application->status) {
        'pending' => 'bg-amber-100 text-amber-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        'cancelled' => 'bg-gray-100 text-gray-600',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

@include('partials.erp.page-header', [
    'title' => 'Leave Application #' . $application->id,
    'subtitle' => $application->employee->name . ' · ' . $application->leaveType->name,
    'actions' => '<a href="' . route('admin.hrm.leave.transactions.index') . '" class="erp-btn-secondary">← Back</a>',
])

@if($application->isPending() && $application->current_approval_step === \App\Services\Hrm\LeaveService::STEP_REPORTING)
    <div class="mb-4 bg-amber-50 border border-amber-200 rounded-sm p-3 text-xs text-amber-800">
        Awaiting approval from reporting person:
        <strong>{{ $application->employee->reportingTo?->name ?? 'Not assigned' }}</strong>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="erp-panel">
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
                    <p class="text-[10px] text-gray-400 uppercase">Factory</p>
                    <p>{{ $application->employee->factory?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Leave Type</p>
                    <p>{{ $application->leaveType->name }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Status</p>
                    <span class="erp-badge {{ $badge }}">{{ $application->statusLabel() }}</span>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Dates</p>
                    <p class="tabular-nums">{{ $application->dateRangeLabel() }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Total Days</p>
                    <p class="tabular-nums font-medium">{{ number_format($application->total_days, 1) }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-[10px] text-gray-400 uppercase">Reason</p>
                    <p class="text-gray-700">{{ $application->reason ?? '—' }}</p>
                </div>
                @if($application->attachment_path)
                    <div class="col-span-2">
                        <p class="text-[10px] text-gray-400 uppercase">Attachment</p>
                        <a href="{{ $application->attachmentUrl() }}" target="_blank" class="erp-btn-sm-secondary">View attachment</a>
                    </div>
                @endif
                @if($application->rejection_reason)
                    <div class="col-span-2">
                        <p class="text-[10px] text-gray-400 uppercase">Rejection Reason</p>
                        <p class="text-red-700">{{ $application->rejection_reason }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="erp-panel overflow-hidden">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Approval Chain</h2>
            </div>
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Step</th>
                        <th>Status</th>
                        <th>Acted By</th>
                        <th>Date</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($application->approvals as $approval)
                        @php
                            $stepBadge = match($approval->status) {
                                'pending' => 'bg-amber-100 text-amber-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                'skipped' => 'bg-gray-100 text-gray-500',
                                default => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <tr>
                            <td class="text-sm">{{ $approval->step_label }}</td>
                            <td><span class="erp-badge {{ $stepBadge }}">{{ $approval->statusLabel() }}</span></td>
                            <td class="text-sm">
                                {{ $approval->actorName() ?? '—' }}
                                @if($approval->status === 'pending' && $approval->approverEmployee)
                                    <span class="block text-[10px] text-gray-400">Awaiting: {{ $approval->approverEmployee->name }}</span>
                                @endif
                            </td>
                            <td class="text-xs text-gray-500 tabular-nums">{{ $approval->acted_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="text-xs text-gray-600">{{ $approval->notes ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($application->isPending() && $application->current_approval_step === \App\Services\Hrm\LeaveService::STEP_HR && auth()->user()->hasPermission('hrm.leave.approve'))
        <div class="space-y-4">
            <div class="erp-panel">
                <div class="erp-panel-head">
                    <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Approve</h2>
                </div>
                <form method="POST" action="{{ route('admin.hrm.leave.transactions.approve', $application) }}" class="erp-panel-body space-y-3">
                    @csrf
                    <div>
                        <label class="erp-form-label">Notes (optional)</label>
                        <textarea name="notes" rows="3" class="erp-input !text-xs" placeholder="Approval notes…"></textarea>
                    </div>
                    <button type="submit" class="erp-btn-primary w-full">Approve Leave</button>
                </form>
            </div>

            <div class="erp-panel">
                <div class="erp-panel-head">
                    <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Reject</h2>
                </div>
                <form method="POST" action="{{ route('admin.hrm.leave.transactions.reject', $application) }}" class="erp-panel-body space-y-3">
                    @csrf
                    <div>
                        <label class="erp-form-label">Rejection Reason</label>
                        <textarea name="rejection_reason" rows="3" class="erp-input !text-xs" required placeholder="Reason for rejection…"></textarea>
                    </div>
                    <button type="submit" class="erp-btn-secondary w-full !text-red-700 !border-red-200">Reject Leave</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
