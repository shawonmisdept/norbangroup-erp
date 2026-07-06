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

    @if($employee->isLineManager())
        <a href="{{ route('employee.team') }}" class="emp-card flex items-center gap-3 p-4 active:bg-gray-50">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700 text-xs font-bold">Team</span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-900">Team Approvals</p>
                <p class="text-xs text-gray-500">Review leave and separation requests from your reportees</p>
            </div>
            <span class="emp-btn-sm-secondary">Open</span>
        </a>
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
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900">{{ $application->leaveType->name }}</p>
                            <p class="text-xs text-gray-500 tabular-nums">{{ $application->dateRangeLabel() }} · {{ number_format($application->total_days, 1) }} day(s)</p>
                            @if($application->pendingStepLabel())
                                <p class="mt-1 text-[10px] font-semibold text-amber-700">
                                    {{ $application->pendingStepLabel() }}
                                    @if($application->current_approval_step === 1 && $application->employee?->reportingTo)
                                        · {{ $application->employee->reportingTo->name }}
                                    @endif
                                </p>
                            @endif
                            @if($application->rejection_reason)
                                <p class="mt-1 text-[10px] text-red-600">{{ $application->rejection_reason }}</p>
                            @endif
                            @if($application->approvals->isNotEmpty())
                                @include('employee.partials.approval-steps', [
                                    'approvals' => $application->approvals,
                                    'pendingStep' => $application->isPending() ? $application->current_approval_step : null,
                                ])
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-2">
                            <span class="emp-badge {{ $badgeClass }}">{{ $application->statusLabel() }}</span>
                            @if($application->isPending())
                                <form method="POST" action="{{ route('employee.leave.cancel', $application) }}"
                                      data-confirm="Cancel this leave application?"
                                      data-confirm-title="Cancel leave"
                                      data-confirm-ok="Yes, cancel">
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
