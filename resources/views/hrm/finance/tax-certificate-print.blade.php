@extends('layouts.payslip-print')

@section('title', 'TDS Certificate — ' . $employee->name)

@section('content')
<div class="payslip-print-toolbar no-print">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">TDS Certificate</p>
            <h1 class="text-lg font-bold text-gray-900">{{ $employee->name }}</h1>
            <p class="text-sm text-gray-500">{{ $employee->employee_code }} · AY {{ $taxYear->label }}</p>
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
            <h2 class="text-xl font-bold text-brand">Income Tax (TDS) Certificate</h2>
            <p class="text-sm text-gray-600">Assessment Year: {{ $taxYear->label }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $taxYear->start_date->format('d M Y') }} – {{ $taxYear->end_date->format('d M Y') }}</p>
        </div>
        <div class="text-right text-sm">
            <p class="font-mono font-semibold">{{ $employee->employee_code }}</p>
            <p class="text-gray-600">{{ $employee->name }}</p>
            <p class="text-xs text-gray-500">{{ $employee->department?->name ?? '—' }}</p>
            <p class="text-xs text-gray-500 mt-1">Issued: {{ now()->format('d M Y') }}</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
        <div class="rounded border border-gray-200 p-3">
            <p class="text-xs uppercase text-gray-400">Factory</p>
            <p class="font-medium">{{ $employee->factory?->name ?? '—' }}</p>
        </div>
        <div class="rounded border border-gray-200 p-3">
            <p class="text-xs uppercase text-gray-400">Designation</p>
            <p class="font-medium">{{ $employee->designation?->name ?? '—' }}</p>
        </div>
    </div>

    <table class="w-full mt-6 text-xs border-collapse">
        <thead>
            <tr class="border-b-2 border-gray-300">
                <th class="text-left py-2">Period</th>
                <th class="text-right py-2">Taxable Income (৳)</th>
                <th class="text-right py-2">TDS Deducted (৳)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ledgers as $row)
                <tr class="border-b border-gray-100">
                    <td class="py-2">{{ $row->year }}-{{ str_pad($row->month, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="py-2 text-right tabular-nums">{{ number_format($row->taxable_income, 2) }}</td>
                    <td class="py-2 text-right tabular-nums">{{ number_format($row->tds_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="border-t-2 border-gray-300 font-semibold">
                <td class="py-2">Total</td>
                <td class="py-2 text-right tabular-nums">৳{{ number_format($totalTaxable, 2) }}</td>
                <td class="py-2 text-right tabular-nums">৳{{ number_format($totalTds, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <p class="mt-8 text-[11px] text-gray-500 leading-relaxed">
        This certificate confirms tax deducted at source (TDS) from salary as per payroll records for the assessment year stated above.
        Generated from {{ config('portal.name') }} HRM system.
    </p>
</div>
@endsection
