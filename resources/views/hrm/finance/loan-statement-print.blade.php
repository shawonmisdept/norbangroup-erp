@extends('layouts.payslip-print')

@section('title', 'Loan Statement — ' . $employee->name)

@section('content')
<div class="payslip-print-toolbar no-print">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Loan Statement</p>
            <h1 class="text-lg font-bold text-gray-900">{{ $employee->name }}</h1>
            <p class="text-sm text-gray-500">{{ $employee->employee_code }} · {{ $loan->loanTypeLabel() }} #{{ $loan->id }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($backUrl ?? null)
                <a href="{{ $backUrl }}" class="erp-btn-secondary">← Back</a>
            @endif
            <button type="button" onclick="window.print()" class="erp-btn-primary">Save as PDF / Print</button>
        </div>
    </div>
</div>

<div class="payslip-print-document">
    <div class="payslip-print-header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">{{ config('portal.name') }}</p>
            <h2 class="text-xl font-bold text-brand">Loan / Advance Statement</h2>
            <p class="text-sm text-gray-600">{{ $loan->loanTypeLabel() }}</p>
        </div>
        <div class="text-right text-sm">
            <p class="font-mono font-semibold">{{ $employee->employee_code }}</p>
            <p class="text-gray-600">{{ $employee->name }}</p>
            <p class="text-xs text-gray-500 mt-1">Generated: {{ now()->format('d M Y') }}</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
        <div class="rounded border border-gray-200 p-3">
            <p class="text-xs uppercase text-gray-400">Principal</p>
            <p class="font-semibold tabular-nums">৳{{ number_format($loan->principal, 2) }}</p>
        </div>
        <div class="rounded border border-gray-200 p-3">
            <p class="text-xs uppercase text-gray-400">Balance</p>
            <p class="font-semibold tabular-nums">৳{{ number_format($loan->balance, 2) }}</p>
        </div>
        <div class="rounded border border-gray-200 p-3">
            <p class="text-xs uppercase text-gray-400">EMI</p>
            <p class="font-semibold tabular-nums">৳{{ number_format($loan->emi_amount, 2) }}</p>
        </div>
        <div class="rounded border border-gray-200 p-3">
            <p class="text-xs uppercase text-gray-400">Status</p>
            <p class="font-semibold">{{ ucfirst($loan->status) }}</p>
        </div>
    </div>

    <table class="w-full mt-6 text-xs border-collapse">
        <thead>
            <tr class="border-b-2 border-gray-300">
                <th class="text-left py-2">#</th>
                <th class="text-left py-2">Due Date</th>
                <th class="text-right py-2">Amount (৳)</th>
                <th class="text-left py-2">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loan->installments as $inst)
                <tr class="border-b border-gray-100">
                    <td class="py-2">{{ $inst->installment_no }}</td>
                    <td class="py-2">{{ $inst->due_date->format('d M Y') }}</td>
                    <td class="py-2 text-right tabular-nums">{{ number_format($inst->amount, 2) }}</td>
                    <td class="py-2">{{ ucfirst($inst->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-4 text-center text-gray-400">No installments recorded.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($loan->notes)
        <p class="mt-6 text-[11px] text-gray-500 whitespace-pre-line">{{ $loan->notes }}</p>
    @endif
</div>
@endsection
