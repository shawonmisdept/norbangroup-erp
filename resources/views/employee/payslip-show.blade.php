@extends('layouts.employee')

@section('title', $payslip->period->periodLabel())
@section('page-title', $payslip->period->periodLabel())
@section('page-subtitle', 'Payslip · ' . $employee->employee_code)
@section('back', route('employee.payslips'))

@section('header-action')
    <div class="flex items-center gap-2">
        <a href="{{ route('employee.payslips.print', ['payslip' => $payslip, 'download' => 1]) }}"
           class="rounded-xl bg-white/15 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur transition hover:bg-white/25">
            Save PDF
        </a>
        <span class="emp-badge {{ $payslip->period->isFrozen() ? 'bg-emerald-400/25 text-emerald-100' : 'bg-amber-400/25 text-amber-100' }}">
            {{ $payslip->period->isFrozen() ? 'Final' : 'Provisional' }}
        </span>
    </div>
@endsection

@section('content')
<div class="emp-payslip space-y-4">
    <div class="emp-card-padded flex items-center justify-between gap-3">
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Net Pay</p>
            <p class="text-3xl font-bold tabular-nums text-brand">৳{{ number_format((float) $payslip->net_pay, 2) }}</p>
        </div>
        <div class="text-right">
            <a href="{{ route('employee.payslips.print', $payslip) }}"
               class="emp-btn-sm-secondary mb-2">
                Print version
            </a>
            <p class="text-xs text-gray-500">{{ $payslip->paidDays() }} paid days · {{ ucfirst($payslip->pay_type) }}</p>
        </div>
    </div>

    @include('hrm.payslip.details', ['payslip' => $payslip, 'employee' => $employee])
</div>
@endsection
