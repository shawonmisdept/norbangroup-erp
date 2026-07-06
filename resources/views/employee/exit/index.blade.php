@extends('layouts.employee')

@section('title', 'Exit & Clearance')
@section('page-title', 'Exit & Clearance')
@section('page-subtitle', $employee->employee_code)

@section('content')
<div class="space-y-5">

    @if($separation)
        <div>
            <p class="emp-section-title">Separation Status</p>
            <div class="emp-card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $separation->typeLabel() }}</p>
                        <p class="text-xs text-gray-500">Last working day: {{ $separation->last_working_day->format('d M Y') }}</p>
                        @if($separation->pendingStepLabel())
                            <p class="mt-1 text-[10px] font-semibold text-amber-700">{{ $separation->pendingStepLabel() }}</p>
                        @endif
                    </div>
                    <span class="emp-badge {{ $separation->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($separation->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                        {{ $separation->statusLabel() }}
                    </span>
                </div>
                @if($separation->approvals->isNotEmpty())
                    @include('employee.partials.approval-steps', [
                        'approvals' => $separation->approvals,
                        'pendingStep' => $separation->isPending() ? $separation->current_approval_step : null,
                    ])
                @endif
            </div>
        </div>
    @else
        <div class="emp-card px-4 py-6 text-center text-sm text-gray-500">
            No separation record on file. Contact HR for resignation or exit processing.
        </div>
    @endif

    @if($separation)
        <div>
            <p class="emp-section-title">Exit Clearance</p>
            <div class="emp-card overflow-hidden">
                @foreach($exitDepartments as $key => $label)
                    @php $done = (bool) ($exitClearance[$key] ?? false); @endphp
                    <div class="flex items-center justify-between gap-3 px-4 py-3.5 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                        <span class="text-sm text-gray-800">{{ $label }}</span>
                        <span class="emp-badge {{ $done ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $done ? 'Cleared' : 'Pending' }}
                        </span>
                    </div>
                @endforeach
            </div>
            @if($separation->exitClearanceComplete())
                <p class="mt-2 px-1 text-[10px] text-emerald-700">All exit clearance departments have signed off.</p>
            @endif
        </div>
    @endif

    @if($settlement && in_array($settlement->status, ['calculated', 'approved', 'paid'], true))
        <div>
            <p class="emp-section-title">Final Settlement</p>
            <a href="{{ route('employee.exit.settlement') }}" class="emp-card block p-4 active:bg-gray-50">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Net Payable</p>
                        <p class="mt-1 text-2xl font-bold tabular-nums text-brand">৳{{ number_format((float) $settlement->net_payable, 2) }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $settlement->statusLabel() }}</p>
                    </div>
                    <span class="emp-btn-sm-secondary">View breakdown</span>
                </div>
            </a>
        </div>
    @endif

    @if($downloadableLetters->isNotEmpty())
        <div>
            <p class="emp-section-title">Exit Letters</p>
            <div class="emp-card overflow-hidden divide-y divide-gray-100">
                @foreach($downloadableLetters as $letter)
                    <div class="flex items-center justify-between gap-3 px-4 py-3.5">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $letter->typeLabel() }}</p>
                            <p class="text-[10px] text-gray-500">{{ $letter->reference_no }}</p>
                        </div>
                        <a href="{{ route('employee.letters.print', $letter) }}" target="_blank" class="emp-btn-sm-secondary">Download</a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($employee->contract_end_date)
        <a href="{{ route('employee.exit.contracts') }}" class="emp-card flex items-center justify-between px-4 py-3.5 text-sm font-semibold text-gray-900">
            Contract renewals
            @include('employee.partials.tab-icon', ['icon' => 'chevron-right'])
        </a>
    @endif
</div>
@endsection
