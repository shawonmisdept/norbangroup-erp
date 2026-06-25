@extends('layouts.employee')

@section('title', 'Loans & Advances')
@section('page-title', 'Loans & Advances')

@section('content')
<div class="space-y-4 pb-4">
    @if($canApply)
        <a href="{{ route('employee.loans.apply') }}" class="emp-btn w-full block text-center">Apply for Loan / Advance</a>
    @elseif($pendingLoan ?? null)
        <div class="emp-card p-4 bg-amber-50 ring-1 ring-amber-100 text-center">
            <p class="text-sm font-semibold text-amber-800">Application pending approval</p>
            <p class="text-xs text-amber-700 mt-1">{{ $pendingLoan->loanTypeLabel() }} — ৳{{ number_format((float) $pendingLoan->principal, 2) }}</p>
        </div>
    @endif

    @if($activeLoan)
        <div class="emp-card p-4 bg-brand/5 ring-1 ring-brand/15">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-brand/70">Active Loan Balance</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">৳{{ number_format((float) $activeLoan->balance, 2) }}</p>
            <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                <div>
                    <p class="text-gray-400">Type</p>
                    <p class="font-medium">{{ $activeLoan->loanTypeLabel() }}</p>
                </div>
                <div>
                    <p class="text-gray-400">Monthly EMI</p>
                    <p class="font-medium tabular-nums">৳{{ number_format((float) $activeLoan->emi_amount, 2) }}</p>
                </div>
                <div>
                    <p class="text-gray-400">Principal</p>
                    <p class="font-medium tabular-nums">৳{{ number_format((float) $activeLoan->principal, 2) }}</p>
                </div>
                <div>
                    <p class="text-gray-400">Paid installments</p>
                    <p class="font-medium">{{ $activeLoan->paid_installments }} / {{ $activeLoan->total_installments }}</p>
                </div>
            </div>
            @php
                $nextEmi = $activeLoan->installments->first(fn ($i) => $i->status === 'pending');
            @endphp
            @if($nextEmi)
                <p class="mt-3 text-xs text-gray-500">Next EMI: ৳{{ number_format((float) $nextEmi->amount, 2) }} due {{ $nextEmi->due_date->format('d M Y') }}</p>
            @endif
        </div>
    @elseif(!($pendingLoan ?? null))
        <div class="emp-card text-center py-8 text-sm text-gray-400">No active loan or advance.</div>
    @endif

    @if($loans->isNotEmpty())
        <div>
            <p class="emp-section-title">History</p>
            <div class="emp-card overflow-hidden divide-y divide-gray-100">
                @foreach($loans as $loan)
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $loan->loanTypeLabel() }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $loan->approved_at?->format('d M Y') ?? ucfirst($loan->status) }}</p>
                            </div>
                            @php
                                $badge = match($loan->status) {
                                    'active' => 'bg-amber-100 text-amber-700',
                                    'closed' => 'bg-emerald-100 text-emerald-700',
                                    'rejected' => 'bg-red-100 text-red-700',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="emp-badge {{ $badge }}">{{ ucfirst($loan->status) }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600">
                            <span>Principal: ৳{{ number_format((float) $loan->principal, 2) }}</span>
                            @if($loan->status !== 'rejected')
                                <span>Balance: ৳{{ number_format((float) $loan->balance, 2) }}</span>
                                <span>EMI: ৳{{ number_format((float) $loan->emi_amount, 2) }}</span>
                            @endif
                        </div>
                        @if($loan->installments->isNotEmpty())
                            <a href="{{ route('employee.loans.statement', $loan) }}?download=1" class="inline-block mt-2 text-xs text-brand font-medium">Download statement PDF</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
