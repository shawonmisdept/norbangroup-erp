@extends('layouts.employee')

@section('title', 'Final Settlement')
@section('page-title', 'Final Settlement')
@section('page-subtitle', $settlement->statusLabel())
@section('back', route('employee.exit'))

@section('header-action')
    <a href="{{ route('employee.exit.settlement.print', ['download' => 1]) }}"
       class="rounded-xl bg-white/15 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur transition hover:bg-white/25">
        Save PDF
    </a>
@endsection

@section('content')
<div class="space-y-4">
    <div class="emp-card-padded">
        <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Net Payable</p>
        <p class="mt-1 text-3xl font-bold tabular-nums text-brand">৳{{ number_format((float) $settlement->net_payable, 2) }}</p>
        <p class="mt-1 text-xs text-gray-500">Last working day: {{ $settlement->last_working_day->format('d M Y') }}</p>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div class="emp-card p-4">
            <p class="text-[10px] uppercase text-gray-400">Earnings</p>
            <p class="mt-1 text-lg font-bold tabular-nums text-emerald-700">৳{{ number_format($settlement->totalEarnings(), 2) }}</p>
        </div>
        <div class="emp-card p-4">
            <p class="text-[10px] uppercase text-gray-400">Deductions</p>
            <p class="mt-1 text-lg font-bold tabular-nums text-red-600">৳{{ number_format($settlement->totalDeductions(), 2) }}</p>
        </div>
    </div>

    <div class="emp-card overflow-hidden divide-y divide-gray-100 text-sm">
        @foreach([
            'Unpaid salary' => $settlement->unpaid_salary,
            'Leave encashment' => $settlement->leave_encashment,
            'Gratuity' => $settlement->gratuity_amount,
            'PF withdrawal' => $settlement->pf_withdrawal,
            'Loan deduction' => -1 * (float) $settlement->loan_deduction,
            'Tax deduction' => -1 * (float) $settlement->tax_deduction,
            'Other earnings' => $settlement->other_earnings,
            'Other deductions' => -1 * (float) $settlement->other_deductions,
        ] as $label => $amount)
            @if(abs((float) $amount) > 0)
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-gray-600">{{ $label }}</span>
                    <span class="font-semibold tabular-nums {{ $amount < 0 ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $amount < 0 ? '−' : '' }}৳{{ number_format(abs((float) $amount), 2) }}
                    </span>
                </div>
            @endif
        @endforeach
    </div>

    @if($settlement->paid_at)
        <p class="px-1 text-xs text-emerald-700">Paid on {{ $settlement->paid_at->format('d M Y') }}</p>
    @endif
</div>
@endsection
