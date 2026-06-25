@extends('layouts.employee')

@section('title', 'Leave')
@section('page-title', 'My Leave')
@section('page-subtitle', $year . ' balance & history')

@section('header-action')
    <a href="{{ route('employee.leave.apply') }}" class="rounded-xl bg-white/15 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur transition hover:bg-white/25">
        + Apply
    </a>
@endsection

@section('content')
<div class="space-y-5">

    @if($pendingApprovals->isNotEmpty())
        <div>
            <p class="emp-section-title">Leave — Needs Your Approval</p>
            <div class="emp-card overflow-hidden border-amber-200/80">
                @foreach($pendingApprovals as $approval)
                    <div class="p-4 {{ !$loop->last ? 'border-b border-amber-100' : '' }}">
                        <div class="mb-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $approval->employee->name }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $approval->leaveType->name }} · {{ $approval->dateRangeLabel() }} · {{ number_format($approval->total_days, 1) }} day(s)</p>
                            @if($approval->reason)
                                <p class="mt-2 rounded-xl bg-gray-50 px-3 py-2 text-xs text-gray-600">{{ $approval->reason }}</p>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('employee.leave.applications.approve', $approval) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="emp-btn w-full !py-2.5 !text-xs">Approve</button>
                            </form>
                            <details class="flex-1">
                                <summary class="emp-btn-secondary w-full cursor-pointer list-none !py-2.5 !text-xs !text-red-600">Reject</summary>
                                <form method="POST" action="{{ route('employee.leave.applications.reject', $approval) }}" class="mt-2 space-y-2">
                                    @csrf
                                    <textarea name="rejection_reason" rows="2" required class="emp-input !text-xs" placeholder="Reason…"></textarea>
                                    <button type="submit" class="emp-btn-secondary w-full !py-2 !text-xs !text-red-700">Confirm</button>
                                </form>
                            </details>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(($pendingSeparationApprovals ?? collect())->isNotEmpty())
        <div>
            <p class="emp-section-title">Separation — Needs Your Approval</p>
            <div class="emp-card overflow-hidden border-orange-200/80">
                @foreach($pendingSeparationApprovals as $sep)
                    <div class="p-4 {{ !$loop->last ? 'border-b border-orange-100' : '' }}">
                        <div class="mb-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $sep->employee->name }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $sep->typeLabel() }} · Last day {{ $sep->last_working_day->format('d M Y') }}</p>
                            @if($sep->reason)
                                <p class="mt-2 rounded-xl bg-gray-50 px-3 py-2 text-xs text-gray-600">{{ $sep->reason }}</p>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('employee.separation.approve', $sep) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="emp-btn w-full !py-2.5 !text-xs">Approve</button>
                            </form>
                            <details class="flex-1">
                                <summary class="emp-btn-secondary w-full cursor-pointer list-none !py-2.5 !text-xs !text-red-600">Reject</summary>
                                <form method="POST" action="{{ route('employee.separation.reject', $sep) }}" class="mt-2 space-y-2">
                                    @csrf
                                    <textarea name="rejection_reason" rows="2" required class="emp-input !text-xs" placeholder="Reason…"></textarea>
                                    <button type="submit" class="emp-btn-secondary w-full !py-2 !text-xs !text-red-700">Confirm</button>
                                </form>
                            </details>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <p class="emp-section-title">Leave Balance ({{ $year }})</p>
        <div class="emp-card overflow-hidden">
            @forelse($balances as $balance)
                <div class="emp-list-item {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900">{{ $balance->leaveType->name }}</p>
                        @if(!$balance->leaveType->is_paid)
                            <p class="text-[10px] text-gray-400">Unpaid</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-base font-bold tabular-nums text-brand">{{ number_format($balance->availableDays(), 1) }}</p>
                        <p class="text-[10px] text-gray-400">available · {{ number_format($balance->used_days, 1) }} used</p>
                    </div>
                </div>
            @empty
                <p class="px-4 py-8 text-center text-sm text-gray-400">No leave balances yet.</p>
            @endforelse
        </div>
    </div>

    <div>
        <p class="emp-section-title">Application History</p>
        <div class="emp-card overflow-hidden">
            @forelse($applications as $application)
                @php
                    $badgeClass = match($application->status) {
                        'pending' => 'bg-amber-100 text-amber-700',
                        'approved' => 'bg-emerald-100 text-emerald-700',
                        'rejected' => 'bg-red-100 text-red-700',
                        'cancelled' => 'bg-gray-100 text-gray-600',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <div class="p-4 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $application->leaveType->name }}</p>
                            <p class="text-xs text-gray-500 tabular-nums">{{ $application->dateRangeLabel() }} · {{ number_format($application->total_days, 1) }} day(s)</p>
                            @if($application->pendingStepLabel())
                                <p class="mt-1 text-[10px] text-amber-700">
                                    {{ $application->pendingStepLabel() }}
                                    @if($application->current_approval_step === 1 && $application->employee?->reportingTo)
                                        · {{ $application->employee->reportingTo->name }}
                                    @endif
                                </p>
                            @endif
                            @if($application->rejection_reason)
                                <p class="mt-1 text-[10px] text-red-600">{{ $application->rejection_reason }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-2">
                            <span class="emp-badge {{ $badgeClass }}">{{ $application->statusLabel() }}</span>
                            @if($application->isPending())
                                <form method="POST" action="{{ route('employee.leave.cancel', $application) }}"
                                      onsubmit="return confirm('Cancel this leave application?')">
                                    @csrf
                                    <button type="submit" class="text-[10px] font-semibold text-red-500">Cancel</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-10 text-center">
                    <p class="text-sm text-gray-500">No leave applications yet.</p>
                    <a href="{{ route('employee.leave.apply') }}" class="mt-3 inline-block emp-btn-sm">Apply for leave</a>
                </div>
            @endforelse
        </div>
        @if($applications->hasPages())
            <div class="mt-3 text-center">{{ $applications->links() }}</div>
        @endif
    </div>
</div>
@endsection
