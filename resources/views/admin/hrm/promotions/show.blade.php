@extends('layouts.admin')

@section('title', 'Promotion #' . $promotion->id)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.promotions.index') }}" class="hover:text-brand">Promotions</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">#{{ $promotion->id }}</span>
@endsection

@section('admin-content')
@php
    $badge = match($promotion->status) {
        'pending'   => 'bg-amber-100 text-amber-800',
        'approved'  => 'bg-green-100 text-green-800',
        'rejected'  => 'bg-red-100 text-red-800',
        'cancelled' => 'bg-gray-100 text-gray-600',
        default     => 'bg-gray-100 text-gray-600',
    };
@endphp

@include('partials.erp.page-header', [
    'title' => $promotion->employee->name . ' — ' . $promotion->movementTypeLabel(),
    'subtitle' => $promotion->employee->employee_code . ' · Effective ' . $promotion->effective_date->format('d M Y'),
    'actions' => '<a href="' . route('admin.hrm.promotions.index') . '" class="erp-btn-secondary">← Back</a>'
        . ($promotion->status === 'approved' ? ' <a href="' . route('admin.hrm.employees.show', $promotion->employee) . '" class="erp-btn-primary !py-2 !px-4 text-xs">Employee Profile</a>' : ''),
])

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Movement Details</h2></div>
            <div class="erp-panel-body space-y-4 text-sm">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase">Employee</p>
                        <p class="font-medium">{{ $promotion->employee->name }}</p>
                        <code class="text-xs text-gray-500">{{ $promotion->employee->employee_code }}</code>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase">Factory</p>
                        <p>{{ $promotion->employee->factory?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase">Type</p>
                        <p>{{ $promotion->movementTypeLabel() }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase">Status</p>
                        <span class="erp-badge {{ $badge }}">{{ $promotion->statusLabel() }}</span>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase">Effective Date</p>
                        <p class="font-medium">{{ $promotion->effective_date->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase">Submitted By</p>
                        <p>{{ $promotion->createdByUser?->name ?? '—' }}</p>
                    </div>
                </div>

                <table class="erp-table text-sm">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>From</th>
                            <th>To</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Designation</td>
                            <td>{{ $promotion->fromDesignation?->name ?? '—' }}</td>
                            <td class="font-medium">{{ $promotion->toDesignation?->name ?? '—' }}</td>
                        </tr>
                        @if($promotion->to_department_id)
                            <tr>
                                <td>Department</td>
                                <td>{{ $promotion->fromDepartment?->name ?? '—' }}</td>
                                <td class="font-medium">{{ $promotion->toDepartment?->name ?? '—' }}</td>
                            </tr>
                        @endif
                        @if($promotion->to_worker_category_id)
                            <tr>
                                <td>Worker Category</td>
                                <td>{{ $promotion->fromWorkerCategory?->name ?? '—' }}</td>
                                <td class="font-medium">{{ $promotion->toWorkerCategory?->name ?? '—' }}</td>
                            </tr>
                        @endif
                        @if($promotion->to_reporting_to_id)
                            <tr>
                                <td>Reporting To</td>
                                <td>{{ $promotion->fromReportingTo?->name ?? '—' }}</td>
                                <td class="font-medium">{{ $promotion->toReportingTo?->name ?? '—' }}</td>
                            </tr>
                        @endif
                        @if($promotion->apply_salary_change)
                            <tr>
                                <td>Salary Grade</td>
                                <td>{{ $promotion->fromSalaryGrade?->name ?? '—' }}</td>
                                <td class="font-medium">{{ $promotion->toSalaryGrade?->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td>Gross Salary</td>
                                <td>{{ $promotion->from_gross_salary !== null ? number_format((float) $promotion->from_gross_salary, 2) : '—' }}</td>
                                <td class="font-medium">{{ $promotion->to_gross_salary !== null ? number_format((float) $promotion->to_gross_salary, 2) : '—' }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                @if($promotion->reason)
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase">Reason</p>
                        <p class="text-gray-700">{{ $promotion->reason }}</p>
                    </div>
                @endif
                @if($promotion->remarks)
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase">HR Remarks</p>
                        <p class="text-gray-700">{{ $promotion->remarks }}</p>
                    </div>
                @endif
                @if($promotion->rejection_reason)
                    <div class="bg-red-50 border border-red-200 rounded-sm p-3">
                        <p class="text-[10px] text-red-600 uppercase">Rejection Reason</p>
                        <p class="text-red-800">{{ $promotion->rejection_reason }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="space-y-4">
        @if($promotion->isPending())
            @if($canApprove)
                <div class="erp-panel">
                    <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">HR Approval</h2></div>
                    <div class="erp-panel-body space-y-3">
                        <form method="POST" action="{{ route('admin.hrm.promotions.approve', $promotion) }}"
                              data-confirm="Approve and apply changes to employee record?"
                              data-confirm-variant="primary"
                              data-confirm-ok="Yes, approve">
                            @csrf
                            <button type="submit" class="erp-btn-primary w-full">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('admin.hrm.promotions.reject', $promotion) }}" class="space-y-2" id="reject"
                              data-confirm="Reject this promotion/demotion request?">
                            @csrf
                            <textarea name="rejection_reason" rows="2" class="erp-input text-xs" placeholder="Rejection reason…" required></textarea>
                            <button type="submit" class="erp-btn-secondary w-full text-red-600">Reject</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="erp-panel">
                    <div class="erp-panel-body text-sm text-amber-700 bg-amber-50 rounded-sm">
                        Awaiting HR approval.
                    </div>
                </div>
            @endif

            @if($canManage)
                <div class="erp-panel">
                    <div class="erp-panel-body">
                        <form method="POST" action="{{ route('admin.hrm.promotions.cancel', $promotion) }}" data-confirm="Cancel this request?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-gray-500 hover:text-red-600">Cancel request</button>
                        </form>
                    </div>
                </div>
            @endif
        @endif

        @if($promotion->approved_at)
            <div class="erp-panel">
                <div class="erp-panel-body text-sm">
                    <p class="text-[10px] text-gray-400 uppercase">Approved</p>
                    <p>@portalDateTime($promotion->approved_at)</p>
                    <p class="text-gray-500">{{ $promotion->approvedByUser?->name }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
