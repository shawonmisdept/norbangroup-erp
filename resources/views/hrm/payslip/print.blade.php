@extends('layouts.payslip-print')

@section('title', $employee->employee_code . ' — ' . $payslip->period->periodLabel())

@section('content')
<div class="payslip-print-toolbar no-print">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Payslip</p>
            <h1 class="text-lg font-bold text-gray-900">{{ $employee->name }}</h1>
            <p class="text-sm text-gray-500">{{ $employee->employee_code }} · {{ $payslip->period->periodLabel() }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($backUrl ?? null)
                <a href="{{ $backUrl }}" class="erp-btn-secondary">← Back</a>
            @endif
            <button type="button" onclick="window.print()" class="erp-btn-primary">Save as PDF / Print</button>
        </div>
    </div>
    <p class="mt-3 text-[11px] text-gray-400">Tip: In the print dialog, choose “Save as PDF” to download.</p>
</div>

<div class="payslip-print-document">
    <div class="payslip-print-header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">{{ config('portal.name') }}</p>
            <h2 class="text-xl font-bold text-brand">Salary Payslip</h2>
            <p class="text-sm text-gray-600">{{ $payslip->period->periodLabel() }}</p>
        </div>
        <div class="text-right text-sm">
            <p class="font-mono font-semibold">{{ $employee->employee_code }}</p>
            <p class="text-gray-600">{{ $employee->name }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ now()->format('d M Y') }}</p>
        </div>
    </div>

    @include('hrm.payslip.details', ['payslip' => $payslip, 'employee' => $employee])
</div>
@endsection
