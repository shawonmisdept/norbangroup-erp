@extends('layouts.payslip-print')

@section('title', 'Final Settlement — ' . $employee->name)

@section('content')
<div class="payslip-print-toolbar no-print">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Full & Final Settlement</p>
            <h1 class="text-lg font-bold text-gray-900">{{ $employee->name }}</h1>
            <p class="text-sm text-gray-500">{{ $employee->employee_code }} · Last day {{ $settlement->last_working_day->format('d M Y') }}</p>
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
            <h2 class="text-xl font-bold text-brand">Full & Final Settlement Sheet</h2>
            <p class="text-sm text-gray-600">{{ ucfirst($settlement->separation_type) }} · {{ $settlement->factory?->name }}</p>
        </div>
        <div class="text-right text-sm">
            <p class="font-mono font-semibold">{{ $employee->employee_code }}</p>
            <p class="text-gray-600">{{ $employee->name }}</p>
            <p class="text-xs text-gray-500 mt-1">Generated: {{ now()->format('d M Y') }}</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
        <div>
            <p class="text-xs uppercase text-gray-400 mb-1">Employee Details</p>
            <p>{{ $employee->department?->name ?? '—' }} · {{ $employee->designation?->name ?? '—' }}</p>
            <p class="text-xs text-gray-500 mt-1">Joining: {{ $employee->joining_date?->format('d M Y') ?? '—' }}</p>
        </div>
        <div class="text-right">
            <p class="text-xs uppercase text-gray-400 mb-1">Settlement</p>
            <p>Status: <strong>{{ $settlement->statusLabel() }}</strong></p>
            <p class="text-xs text-gray-500">Ref #{{ $settlement->id }}</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <p class="text-xs font-semibold uppercase text-gray-500 mb-2 border-b pb-1">Earnings (+)</p>
            <table class="w-full text-xs">
                <tr><td class="py-1">Unpaid salary</td><td class="py-1 text-right tabular-nums">৳{{ number_format($settlement->unpaid_salary, 2) }}</td></tr>
                <tr><td class="py-1">Leave encashment</td><td class="py-1 text-right tabular-nums">৳{{ number_format($settlement->leave_encashment, 2) }}</td></tr>
                <tr><td class="py-1">Gratuity</td><td class="py-1 text-right tabular-nums">৳{{ number_format($settlement->gratuity_amount, 2) }}</td></tr>
                <tr><td class="py-1">PF withdrawal</td><td class="py-1 text-right tabular-nums">৳{{ number_format($settlement->pf_withdrawal, 2) }}</td></tr>
                @if($settlement->other_earnings > 0)
                <tr><td class="py-1">Other earnings</td><td class="py-1 text-right tabular-nums">৳{{ number_format($settlement->other_earnings, 2) }}</td></tr>
                @endif
                <tr class="font-bold border-t"><td class="py-2">Total earnings</td><td class="py-2 text-right tabular-nums">৳{{ number_format($settlement->totalEarnings(), 2) }}</td></tr>
            </table>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-gray-500 mb-2 border-b pb-1">Deductions (−)</p>
            <table class="w-full text-xs">
                <tr><td class="py-1">Outstanding loans</td><td class="py-1 text-right tabular-nums">৳{{ number_format($settlement->loan_deduction, 2) }}</td></tr>
                <tr><td class="py-1">Tax / TDS</td><td class="py-1 text-right tabular-nums">৳{{ number_format($settlement->tax_deduction, 2) }}</td></tr>
                @if($settlement->other_deductions > 0)
                <tr><td class="py-1">Other deductions</td><td class="py-1 text-right tabular-nums">৳{{ number_format($settlement->other_deductions, 2) }}</td></tr>
                @endif
                <tr class="font-bold border-t"><td class="py-2">Total deductions</td><td class="py-2 text-right tabular-nums">৳{{ number_format($settlement->totalDeductions(), 2) }}</td></tr>
            </table>
        </div>
    </div>

    <div class="mt-8 p-4 border-2 border-brand/30 rounded text-center">
        <p class="text-xs uppercase text-gray-500">Net Payable (Full & Final)</p>
        <p class="text-3xl font-bold text-brand tabular-nums mt-1">৳{{ number_format($settlement->net_payable, 2) }}</p>
    </div>

    @php $clearance = array_merge(\App\Models\Hrm\FinalSettlement::defaultClearance(), $settlement->clearance ?? []); @endphp
    <div class="mt-8">
        <p class="text-xs font-semibold uppercase text-gray-500 mb-2">Exit Clearance</p>
        <div class="grid grid-cols-5 gap-2 text-[11px] text-center">
            @foreach(\App\Models\Hrm\FinalSettlement::CLEARANCE_KEYS as $key => $label)
                <div class="border rounded p-2 {{ !empty($clearance[$key]) ? 'bg-green-50 border-green-200' : 'bg-gray-50' }}">
                    <p>{{ $label }}</p>
                    <p class="font-semibold mt-1">{{ !empty($clearance[$key]) ? '✓ Cleared' : 'Pending' }}</p>
                </div>
            @endforeach
        </div>
    </div>

    @if($settlement->notes)
        <p class="mt-6 text-[11px] text-gray-500 whitespace-pre-line">{{ $settlement->notes }}</p>
    @endif

    <div class="mt-12 grid grid-cols-3 gap-8 text-xs text-center text-gray-500">
        <div class="border-t pt-2">HR Signature</div>
        <div class="border-t pt-2">Accounts Signature</div>
        <div class="border-t pt-2">Employee Signature</div>
    </div>
</div>
@endsection
