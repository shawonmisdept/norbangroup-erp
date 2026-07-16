@extends('layouts.admin')

@section('title', 'Separation #' . $separation->id)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.separations.index') }}" class="hover:text-brand">Separations</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">#{{ $separation->id }}</span>
@endsection

@section('admin-content')
@php
    $badge = match($separation->status) {
        'pending' => 'bg-amber-100 text-amber-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

@include('partials.erp.page-header', [
    'title' => $separation->employee->name . ' — ' . $separation->typeLabel(),
    'subtitle' => $separation->employee->employee_code . ' · Last day ' . $separation->last_working_day->format('d M Y'),
    'actions' => '<a href="' . route('admin.hrm.separations.index') . '" class="erp-btn-secondary">← Back</a>'
        . ($separation->status === 'approved' ? ' <a href="' . route('admin.hrm.employees.show', $separation->employee) . '" class="erp-btn-primary !py-2 !px-4 text-xs">Employee Profile</a>' : ''),
])

@if($separation->isPending() && $separation->current_approval_step === \App\Services\Hrm\EmployeeSeparationService::STEP_REPORTING)
    <div class="mb-4 bg-amber-50 border border-amber-200 rounded-sm p-3 text-xs text-amber-800">
        Awaiting approval from reporting person: <strong>{{ $separation->employee->reportingTo?->name ?? 'Not assigned' }}</strong>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Details</h2></div>
            <div class="erp-panel-body grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Employee</p>
                    <p class="font-medium">{{ $separation->employee->name }}</p>
                    <code class="text-xs text-gray-500">{{ $separation->employee->employee_code }}</code>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Factory</p>
                    <p>{{ $separation->employee->factory?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Type</p>
                    <p>{{ $separation->typeLabel() }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Status</p>
                    <span class="erp-badge {{ $badge }}">{{ $separation->statusLabel() }}</span>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Application Date</p>
                    <p>{{ $separation->application_date->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Last Working Day</p>
                    <p class="font-medium">{{ $separation->last_working_day->format('d M Y') }}</p>
                </div>
                @if($separation->notice_period_days)
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase">Notice Period</p>
                        <p>{{ $separation->notice_period_days }} days</p>
                    </div>
                @endif
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Source</p>
                    <p>{{ ucfirst($separation->source) }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-[10px] text-gray-400 uppercase">Reason</p>
                    <p class="text-gray-700">{{ $separation->reason ?? '—' }}</p>
                </div>
                @if($separation->remarks)
                    <div class="col-span-2">
                        <p class="text-[10px] text-gray-400 uppercase">HR Remarks</p>
                        <p class="text-gray-700">{{ $separation->remarks }}</p>
                    </div>
                @endif
                @if($separation->attachment_path)
                    <div class="col-span-2">
                        <a href="{{ $separation->attachmentUrl() }}" target="_blank" class="erp-btn-sm-secondary">View attachment</a>
                    </div>
                @endif
                @if($separation->rejection_reason)
                    <div class="col-span-2">
                        <p class="text-[10px] text-gray-400 uppercase">Rejection Reason</p>
                        <p class="text-red-700">{{ $separation->rejection_reason }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Approval Trail</h2></div>
            <div class="erp-panel-body space-y-3">
                @foreach($separation->approvals as $approval)
                    @php
                        $stepBadge = match($approval->status) {
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            'skipped' => 'bg-gray-100 text-gray-500',
                            default => 'bg-amber-100 text-amber-800',
                        };
                    @endphp
                    <div class="flex items-start justify-between gap-3 border border-erp-border rounded-sm p-3">
                        <div>
                            <p class="text-sm font-medium">{{ $approval->step_label }}</p>
                            @if($approval->approverEmployee)
                                <p class="text-xs text-gray-500">Approver: {{ $approval->approverEmployee->name }}</p>
                            @endif
                            @if($approval->actorName())
                                <p class="text-xs text-gray-500">By {{ $approval->actorName() }} · @portalDateTime($approval->acted_at)</p>
                            @endif
                            @if($approval->notes)
                                <p class="text-xs text-gray-600 mt-1">{{ $approval->notes }}</p>
                            @endif
                        </div>
                        <span class="erp-badge {{ $stepBadge }} shrink-0">{{ $approval->statusLabel() }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        @if($separation->isPending() && $separation->current_approval_step === \App\Services\Hrm\EmployeeSeparationService::STEP_HR)
            @php
                $clearance = $separation->exit_clearance ?? \App\Models\Hrm\EmployeeSeparation::defaultExitClearance();
                $departments = config('hrm.exit_clearance_departments', []);
            @endphp
            <div class="erp-panel">
                <div class="erp-panel-head">
                    <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Exit Clearance & Interview</h2>
                    @if($separation->exitClearanceComplete())
                        <span class="erp-badge bg-green-100 text-green-800 text-[10px]">Clearance Complete</span>
                    @else
                        <span class="erp-badge bg-amber-100 text-amber-800 text-[10px]">Pending Clearance</span>
                    @endif
                </div>
                @if($canApprove)
                    <form method="POST" action="{{ route('admin.hrm.separations.exit-data', $separation) }}" class="erp-panel-body space-y-4">
                        @csrf
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($departments as $key => $label)
                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                    <input type="checkbox" name="exit_clearance[{{ $key }}]" value="1"
                                           {{ ! empty($clearance[$key]) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-brand focus:ring-brand">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div>
                            <label class="erp-form-label">Exit Interview Notes</label>
                            <textarea name="exit_interview_notes" rows="4" class="erp-input !text-xs" placeholder="Feedback, reason for leaving, handover notes…">{{ old('exit_interview_notes', $separation->exit_interview_notes) }}</textarea>
                            @if($separation->exit_interview_at)
                                <p class="text-[10px] text-gray-400 mt-1">Last saved @portalDateTime($separation->exit_interview_at)</p>
                            @endif
                        </div>
                        <button type="submit" class="erp-btn-secondary !py-2 !px-4 text-xs">Save Exit Data</button>
                    </form>
                @else
                    <div class="erp-panel-body space-y-3 text-sm">
                        <div class="flex flex-wrap gap-2">
                            @foreach($departments as $key => $label)
                                <span class="erp-badge {{ ! empty($clearance[$key]) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $label }}{{ ! empty($clearance[$key]) ? ' ✓' : '' }}
                                </span>
                            @endforeach
                        </div>
                        @if($separation->exit_interview_notes)
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase">Exit Interview</p>
                                <p class="text-gray-700 whitespace-pre-wrap">{{ $separation->exit_interview_notes }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @elseif($separation->status === 'approved' && ($separation->exit_clearance || $separation->exit_interview_notes))
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Exit Clearance & Interview</h2></div>
                <div class="erp-panel-body space-y-3 text-sm">
                    @if($separation->exit_clearance)
                        <div class="flex flex-wrap gap-2">
                            @foreach(config('hrm.exit_clearance_departments', []) as $key => $label)
                                <span class="erp-badge {{ ! empty($separation->exit_clearance[$key]) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $label }}{{ ! empty($separation->exit_clearance[$key]) ? ' ✓' : '' }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                    @if($separation->exit_interview_notes)
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase">Exit Interview</p>
                            <p class="text-gray-700 whitespace-pre-wrap">{{ $separation->exit_interview_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="space-y-3">
        @if($separation->isPending() && $separation->current_approval_step === \App\Services\Hrm\EmployeeSeparationService::STEP_REPORTING && auth()->user()->isReportingManagerFor($separation->employee))
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Team Approval</h2></div>
                <p class="erp-panel-body text-xs text-gray-500 pb-0">You are the reporting person. Approve here to forward to HR.</p>
                <form method="POST" action="{{ route('admin.hrm.separations.approve-reporting', $separation) }}" class="erp-panel-body space-y-3 pt-2"
                      data-confirm="Forward this separation request to HR?"
                      data-confirm-variant="primary"
                      data-confirm-ok="Yes, approve">
                    @csrf
                    <textarea name="notes" rows="2" class="erp-input !text-xs" placeholder="Approval notes (optional)"></textarea>
                    <button type="submit" class="erp-btn-primary w-full justify-center">Approve &amp; Forward to HR</button>
                </form>
            </div>
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold text-red-700 uppercase">Reject</h2></div>
                <form method="POST" action="{{ route('admin.hrm.separations.reject-reporting', $separation) }}" class="erp-panel-body space-y-3"
                      data-confirm="Reject this separation request?">
                    @csrf
                    <textarea name="rejection_reason" rows="2" required class="erp-input !text-xs" placeholder="Rejection reason…"></textarea>
                    <button type="submit" class="erp-btn-danger w-full justify-center">Reject Request</button>
                </form>
            </div>
        @elseif($separation->isPending() && $separation->current_approval_step === \App\Services\Hrm\EmployeeSeparationService::STEP_HR && $canApprove)
            @if(! $separation->exitClearanceComplete())
                <div class="bg-amber-50 border border-amber-200 rounded-sm p-3 text-xs text-amber-800">
                    Complete all exit clearance departments before approving separation.
                </div>
            @endif
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">HR Approval</h2></div>
                <form method="POST" action="{{ route('admin.hrm.separations.approve', $separation) }}" class="erp-panel-body space-y-3"
                      data-confirm="Approve separation? Employee status will be updated."
                      data-confirm-variant="primary"
                      data-confirm-ok="Yes, approve">
                    @csrf
                    <textarea name="notes" rows="2" class="erp-input !text-xs" placeholder="Approval notes (optional)"></textarea>
                    <button type="submit" class="erp-btn-primary w-full justify-center" {{ ! $separation->exitClearanceComplete() ? 'disabled' : '' }}>Approve Separation</button>
                </form>
            </div>
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold text-red-700 uppercase">Reject</h2></div>
                <form method="POST" action="{{ route('admin.hrm.separations.reject', $separation) }}" class="erp-panel-body space-y-3"
                      data-confirm="Reject this separation request?">
                    @csrf
                    <textarea name="rejection_reason" rows="2" required class="erp-input !text-xs" placeholder="Rejection reason…"></textarea>
                    <button type="submit" class="erp-btn-danger w-full justify-center">Reject Request</button>
                </form>
            </div>
        @endif

        @if($separation->isPending() && $canManage)
            <form method="POST" action="{{ route('admin.hrm.separations.cancel', $separation) }}" data-confirm="Cancel this separation request?">
                @csrf
                @method('DELETE')
                <button type="submit" class="erp-btn-secondary w-full justify-center !text-amber-700">Cancel Request</button>
            </form>
        @endif

        @if($separation->status === 'approved')
            <div class="erp-panel">
                <div class="erp-panel-body text-xs text-gray-500 space-y-2">
                    <p>Employee status: <strong class="text-gray-800">{{ $separation->employee->statusLabel() }}</strong></p>
                    @if($canManageSettlement)
                        <a href="{{ route('admin.hrm.finance.final-settlement.create', ['employee_id' => $separation->employee_id]) }}" class="erp-btn-sm-primary w-full justify-center">Start F&F Settlement</a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
