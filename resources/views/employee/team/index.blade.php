@extends('layouts.employee')

@section('title', 'Team Approvals')
@section('page-title', 'Team Approvals')
@section('page-subtitle', 'Leave & separation requests')

@section('content')
<div class="space-y-5">

    @if($pendingLeave->isNotEmpty())
        <div>
            <p class="emp-section-title">Leave — Needs Your Approval</p>
            <div class="emp-card overflow-hidden border-amber-200/80">
                @foreach($pendingLeave as $approval)
                    <div class="p-4 {{ !$loop->last ? 'border-b border-amber-100' : '' }}">
                        <div class="mb-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $approval->employee->name }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $approval->leaveType->name }} · {{ $approval->dateRangeLabel() }} · {{ number_format($approval->total_days, 1) }} day(s)</p>
                            @if($approval->reason)
                                <p class="mt-2 rounded-xl bg-gray-50 px-3 py-2 text-xs text-gray-600">{{ $approval->reason }}</p>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('employee.team.leave.approve', $approval) }}" class="flex-1"
                                  data-confirm="Approve leave for {{ $approval->employee->name }}?"
                                  data-confirm-variant="primary"
                                  data-confirm-ok="Yes, approve">
                                @csrf
                                <button type="submit" class="emp-btn w-full !py-2.5 !text-xs">Approve</button>
                            </form>
                            <details class="flex-1">
                                <summary class="emp-btn-secondary w-full cursor-pointer list-none !py-2.5 !text-xs !text-red-600">Reject</summary>
                                <form method="POST" action="{{ route('employee.team.leave.reject', $approval) }}" class="mt-2 space-y-2"
                                      data-confirm="Reject leave for {{ $approval->employee->name }}?"
                                      data-confirm-variant="danger"
                                      data-confirm-ok="Yes, reject">
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

    @if($pendingSeparations->isNotEmpty())
        <div>
            <p class="emp-section-title">Separation — Needs Your Approval</p>
            <div class="emp-card overflow-hidden border-violet-200/80">
                @foreach($pendingSeparations as $separation)
                    <div class="p-4 {{ !$loop->last ? 'border-b border-violet-100' : '' }}">
                        <div class="mb-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $separation->employee->name }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $separation->typeLabel() }} · Last day {{ $separation->last_working_day->format('d M Y') }}</p>
                            @if($separation->reason)
                                <p class="mt-2 rounded-xl bg-gray-50 px-3 py-2 text-xs text-gray-600">{{ Str::limit($separation->reason, 200) }}</p>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('employee.team.separation.approve', $separation) }}" class="flex-1"
                                  data-confirm="Approve separation for {{ $separation->employee->name }}?"
                                  data-confirm-variant="primary"
                                  data-confirm-ok="Yes, approve">
                                @csrf
                                <button type="submit" class="emp-btn w-full !py-2.5 !text-xs">Approve</button>
                            </form>
                            <details class="flex-1">
                                <summary class="emp-btn-secondary w-full cursor-pointer list-none !py-2.5 !text-xs !text-red-600">Reject</summary>
                                <form method="POST" action="{{ route('employee.team.separation.reject', $separation) }}" class="mt-2 space-y-2"
                                      data-confirm="Reject separation for {{ $separation->employee->name }}?"
                                      data-confirm-variant="danger"
                                      data-confirm-ok="Yes, reject">
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

    @if($pendingLeave->isEmpty() && $pendingSeparations->isEmpty())
        <div class="emp-card px-6 py-12 text-center">
            <p class="text-sm text-gray-500">No pending team approvals right now.</p>
        </div>
    @endif
</div>
@endsection
