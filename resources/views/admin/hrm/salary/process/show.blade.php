@extends('layouts.admin')

@section('title', $period->periodLabel() . ' Payroll — ' . config('app.name'))

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.salary.hub') }}" class="hover:text-brand">Payroll</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.salary.process.index') }}" class="hover:text-brand">Periods</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $period->periodLabel() }}</span>
@endsection

@section('admin-content')
@php
    $statusBadge = match($period->status) {
        'draft' => 'bg-gray-100 text-gray-600',
        'calculated' => 'bg-blue-100 text-blue-800',
        'frozen' => 'bg-green-100 text-green-800',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

@php
    $processActions = '<div class="flex flex-wrap gap-2 items-center">'
        . '<span class="erp-badge ' . $statusBadge . '">' . e($period->statusLabel()) . '</span>'
        . '<a href="' . route('admin.hrm.salary.process.index') . '" class="erp-btn-secondary">← Periods</a>';
    if ($period->status === 'calculated' && auth()->user()->hasPermission('hrm.salary.approve')) {
        $processActions .= '<form method="POST" action="' . route('admin.hrm.salary.close.freeze', $period) . '" class="inline"'
            . ' data-confirm="Close ' . e($period->periodLabel()) . ' and email payslips?" data-confirm-variant="warning" data-confirm-ok="Yes, close">'
            . csrf_field()
            . '<input type="hidden" name="send_payslips" value="1">'
            . '<button type="submit" class="erp-btn-primary !py-2 !px-4 text-xs">Close Period</button></form>';
    }
    if ($period->isFrozen() && auth()->user()->hasPermission('hrm.salary.approve')) {
        $processActions .= '<a href="' . route('admin.hrm.salary.close.bank-advise', $period) . '" class="erp-btn-secondary">Bank Advise CSV</a>';
    }
    $processActions .= '</div>';
@endphp

@include('partials.erp.page-header', [
    'title' => $period->periodLabel() . ' Payroll',
    'subtitle' => $period->factory?->name . ' · ' . $period->start_date->format('d M') . ' – ' . $period->end_date->format('d M Y'),
    'actions' => $processActions,
])

<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
    <div class="erp-panel">
        <div class="erp-panel-body">
            <p class="text-2xl font-bold text-gray-900">{{ $totals->headcount ?? 0 }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Employees</p>
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-body">
            <p class="text-2xl font-bold text-blue-700">৳{{ number_format((float) ($totals->gross_total ?? 0), 2) }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Gross Total</p>
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-body">
            <p class="text-2xl font-bold text-green-700">৳{{ number_format((float) ($totals->net_total ?? 0), 2) }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Net Total</p>
        </div>
    </div>
</div>

@if($period->attendancePeriod)
<div class="erp-panel mb-4">
    <div class="erp-panel-body text-xs text-gray-600">
        Attendance period: <span class="font-medium">{{ $period->attendancePeriod->statusLabel() }}</span>
        @if($period->calculated_at)
            · Calculated @portalDateTime($period->calculated_at)
            @if($period->calculatedByUser) by {{ $period->calculatedByUser->name }} @endif
        @endif
        @if($period->frozen_at)
            · Frozen @portalDateTime($period->frozen_at)
            @if($period->frozenByUser) by {{ $period->frozenByUser->name }} @endif
        @endif
    </div>
</div>
@endif

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="erp-form-label">Search employee</label>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or code…" class="erp-input !text-xs">
            </div>
            <button type="submit" class="erp-btn-secondary">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Payroll Items</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Pay Type</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Late</th>
                    <th>Gross</th>
                    <th>Deductions</th>
                    <th>Net Pay</th>
                    <th class="w-24"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>
                            <a href="{{ route('admin.hrm.salary.process.payslip', [$period, $item]) }}" class="group block hover:text-brand">
                                <p class="font-medium text-sm group-hover:underline">{{ $item->employee->name }}</p>
                                <code class="text-[10px] text-gray-400">{{ $item->employee->employee_code }}</code>
                            </a>
                        </td>
                        <td class="text-xs">{{ ucfirst($item->pay_type) }}</td>
                        <td class="text-sm tabular-nums">{{ $item->present_days }}</td>
                        <td class="text-sm tabular-nums">{{ $item->absent_days }}</td>
                        <td class="text-sm tabular-nums">{{ $item->late_days }}</td>
                        <td class="text-sm tabular-nums">৳{{ number_format((float) $item->gross_pay, 2) }}</td>
                        <td class="text-sm tabular-nums text-red-600">৳{{ number_format($item->totalDeductions(), 2) }}</td>
                        <td class="text-sm tabular-nums font-semibold">৳{{ number_format((float) $item->net_pay, 2) }}</td>
                        <td>
                            @include('partials.erp.table-actions', [
                                'viewUrl' => route('admin.hrm.salary.process.payslip', [$period, $item]),
                                'viewLabel' => 'Payslip',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-10 text-gray-400">
                            @if($period->status === 'draft')
                                No payroll calculated yet. Run Calculate from the Periods page.
                            @else
                                No payroll items for this period.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($items->hasPages())
        <div class="px-4 py-3 border-t border-erp-border bg-gray-50/50">{{ $items->links() }}</div>
    @endif
</div>
@endsection
