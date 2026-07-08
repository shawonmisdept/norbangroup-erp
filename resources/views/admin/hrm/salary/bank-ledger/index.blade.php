@extends('layouts.admin')

@section('title', 'Bank Payment Register')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.salary.hub') }}" class="hover:text-brand">Payroll</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.salary.close.index') }}" class="hover:text-brand">Salary Close</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Bank Ledger</span>
@endsection

@push('styles')
<style>
    @media print {
        .erp-topbar, .erp-sidebar, .erp-breadcrumbs, .bank-ledger-no-print { display: none !important; }
        .bank-ledger-print-only { display: block !important; }
        .erp-panel { break-inside: avoid; box-shadow: none !important; }
    }
    .bank-ledger-print-only { display: none; }
</style>
@endpush

@section('admin-content')
@php
    $headerActions = '<div class="flex flex-wrap gap-2 items-center bank-ledger-no-print">';
    $headerActions .= '<a href="' . route('admin.hrm.salary.close.index') . '" class="erp-btn-secondary">← Salary Close</a>';

    if ($selectedPeriod) {
        $headerActions .= '<a href="' . route('admin.hrm.salary.process.show', $selectedPeriod) . '" class="erp-btn-secondary">View Period</a>';

        if (auth()->user()->canViewSalarySubmodule('close')) {
            $headerActions .= '<a href="' . route('admin.hrm.salary.disbursement.show', $selectedPeriod) . '" class="erp-btn-secondary">Disbursement</a>';
        }

        if (auth()->user()->hasPermission('hrm.salary.approve')) {
            $headerActions .= '<a href="' . route('admin.hrm.salary.close.bank-advise', $selectedPeriod) . '" class="erp-btn-secondary">Bank Advise CSV</a>';
            $headerActions .= '<a href="' . route('admin.hrm.salary.close.cash-list', $selectedPeriod) . '" class="erp-btn-secondary">Cash CSV</a>';
        }

        $headerActions .= '<a href="' . route('admin.hrm.salary.bank-ledger.export-summary', array_merge($filters, ['payroll_period_id' => $selectedPeriod->id])) . '" class="erp-btn-secondary">Summary CSV</a>';
        $headerActions .= '<a href="' . route('admin.hrm.salary.bank-ledger.export-detail', array_merge($filters, ['payroll_period_id' => $selectedPeriod->id])) . '" class="erp-btn-secondary">Detail CSV</a>';
        $headerActions .= '<button type="button" onclick="window.print()" class="erp-btn-secondary">Print</button>';
    }

    $headerActions .= '</div>';
@endphp

@include('partials.erp.page-header', [
    'title' => 'Bank Payment Register',
    'subtitle' => 'Closed payroll — bank-wise salary transfer ledger',
    'actions' => $headerActions,
])

@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'bank-ledger'])

<div class="erp-panel mb-4 bank-ledger-no-print">
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.salary.bank-ledger.index') }}" class="erp-filter-bar">
            @if(count($factories) > 1)
            <div class="erp-filter-field">
                <label class="erp-form-label">Factory</label>
                <select name="factory_id" class="erp-input !text-xs" onchange="this.form.payroll_period_id.value=''; this.form.submit()">
                    <option value="">Select factory…</option>
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($filters['factory_id'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="erp-filter-field">
                <label class="erp-form-label">Closed Period <span class="text-red-500">*</span></label>
                <select name="payroll_period_id" required class="erp-input !text-xs">
                    <option value="">Select period…</option>
                    @foreach($periods as $period)
                        <option value="{{ $period->id }}" @selected((string) ($filters['payroll_period_id'] ?? $selectedPeriod?->id) === (string) $period->id)>
                            {{ $period->periodLabel() }} — {{ $period->factory?->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="erp-filter-field">
                <label class="erp-form-label">Bank</label>
                <select name="salary_bank_id" class="erp-input !text-xs">
                    <option value="">All banks</option>
                    @foreach($bankOptions as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['salary_bank_id'] ?? '') === (string) $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="erp-filter-field-grow">
                <label class="erp-form-label">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="erp-input !text-xs" placeholder="Code or name">
            </div>
            <div class="erp-filter-actions">
                <button type="submit" class="erp-btn-secondary !py-2">Show</button>
                @if($selectedPeriod && (($filters['salary_bank_id'] ?? '') !== '' || ($filters['search'] ?? '') !== ''))
                    <a href="{{ route('admin.hrm.salary.bank-ledger.index', ['payroll_period_id' => $selectedPeriod->id, 'factory_id' => $selectedPeriod->factory_id]) }}" class="erp-btn-secondary !py-2">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

@if(! $selectedPeriod)
<div class="erp-panel"><div class="erp-panel-body text-center py-12 text-gray-400">Select a closed payroll period to view the bank ledger.</div></div>
@else
    <p class="bank-ledger-print-only text-sm text-gray-600 mb-3">
        {{ $selectedPeriod->periodLabel() }} — {{ $selectedPeriod->factory?->name }} · Printed @portalDateTime(now())
    </p>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="erp-panel"><div class="erp-panel-body text-center"><p class="text-[11px] text-gray-500 uppercase">Employees</p><p class="text-lg font-bold tabular-nums">{{ $totals['headcount'] }}</p></div></div>
        <div class="erp-panel"><div class="erp-panel-body text-center"><p class="text-[11px] text-gray-500 uppercase">Bank Pay</p><p class="text-lg font-bold tabular-nums text-blue-700">৳{{ number_format($totals['bank_total'], 2) }}</p></div></div>
        <div class="erp-panel"><div class="erp-panel-body text-center"><p class="text-[11px] text-gray-500 uppercase">Cash Pay</p><p class="text-lg font-bold tabular-nums text-orange-700">৳{{ number_format($totals['cash_total'], 2) }}</p></div></div>
        <div class="erp-panel"><div class="erp-panel-body text-center"><p class="text-[11px] text-gray-500 uppercase">Net Pay</p><p class="text-lg font-bold tabular-nums">৳{{ number_format($totals['net_total'], 2) }}</p></div></div>
    </div>

    @if($unassignedCount > 0)
    <div class="mb-4 rounded-sm border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 bank-ledger-no-print">
        <strong>{{ $unassignedCount }}</strong> employee(s) have bank pay but no salary bank assigned.
        <a href="{{ route('admin.hrm.salary.bank-ledger.index', ['payroll_period_id' => $selectedPeriod->id, 'salary_bank_id' => 'unassigned']) }}" class="underline font-medium">View unassigned</a>
        · <a href="{{ route('admin.hrm.salary.employee-salary.index') }}" class="underline font-medium">Fix in Employee Salary</a>
    </div>
    @endif

    <div class="erp-panel mb-4 overflow-hidden">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase tracking-wide text-gray-600">Bank Summary — {{ $selectedPeriod->periodLabel() }}</h2></div>
        <div class="overflow-x-auto">
            <table class="erp-table min-w-[36rem]">
                <thead>
                    <tr>
                        <th>Bank</th>
                        <th class="text-right w-28">Employees</th>
                        <th class="text-right w-36">Bank Pay</th>
                        <th class="text-right w-36">Cash Pay</th>
                        <th class="text-right w-36">Net Pay</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary as $row)
                    <tr class="{{ $row['is_unassigned'] ? 'bg-amber-50/50' : '' }}">
                        <td>
                            <a href="{{ route('admin.hrm.salary.bank-ledger.index', array_merge($filters, ['payroll_period_id' => $selectedPeriod->id, 'salary_bank_id' => $row['bank_key']])) }}" class="font-medium text-sm hover:text-brand bank-ledger-no-print">
                                {{ $row['bank_name'] }}
                            </a>
                            <span class="font-medium text-sm bank-ledger-print-only">{{ $row['bank_name'] }}</span>
                        </td>
                        <td class="text-right tabular-nums">{{ $row['headcount'] }}</td>
                        <td class="text-right tabular-nums text-blue-700">৳{{ number_format($row['bank_total'], 2) }}</td>
                        <td class="text-right tabular-nums text-orange-700">৳{{ number_format($row['cash_total'], 2) }}</td>
                        <td class="text-right tabular-nums">৳{{ number_format($row['net_total'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-8 text-gray-400">No bank payments for this period.</td></tr>
                    @endforelse
                    @if($summary->isNotEmpty())
                    <tr class="font-semibold bg-gray-50/80">
                        <td>Grand Total</td>
                        <td class="text-right tabular-nums">{{ $totals['headcount'] }}</td>
                        <td class="text-right tabular-nums text-blue-700">৳{{ number_format($totals['bank_total'], 2) }}</td>
                        <td class="text-right tabular-nums text-orange-700">৳{{ number_format($totals['cash_total'], 2) }}</td>
                        <td class="text-right tabular-nums">৳{{ number_format($totals['net_total'], 2) }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="erp-panel overflow-hidden">
        <div class="erp-panel-head flex items-center justify-between gap-2">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-600">Ledger Detail</h2>
            <span class="text-[11px] text-gray-400">{{ $items?->total() ?? 0 }} row(s)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table min-w-[48rem]">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Bank</th>
                        <th>Account</th>
                        <th class="text-right w-32">Bank Pay</th>
                        <th class="text-right w-32">Cash Pay</th>
                        <th class="text-right w-32">Net Pay</th>
                        <th class="w-28">Cash Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items ?? [] as $item)
                    @php $hasCash = (float) $item->cash_pay_amount > 0; @endphp
                    <tr class="{{ $item->salary_bank_id ? '' : 'bg-amber-50/40' }}">
                        <td>
                            <p class="font-medium text-sm">{{ $item->employee?->name }}</p>
                            <p class="text-[11px] text-gray-500 font-mono">{{ $item->employee?->employee_code }}</p>
                        </td>
                        <td class="text-xs">{{ $item->salaryBank?->displayName() ?? 'Unassigned Bank' }}</td>
                        <td class="text-xs font-mono">{{ $item->bank_account ?: '—' }}</td>
                        <td class="text-right tabular-nums text-blue-700">৳{{ number_format((float) $item->bank_pay_amount, 2) }}</td>
                        <td class="text-right tabular-nums text-orange-700">৳{{ number_format((float) $item->cash_pay_amount, 2) }}</td>
                        <td class="text-right tabular-nums">৳{{ number_format((float) $item->net_pay, 2) }}</td>
                        <td>
                            @if(! $hasCash)
                                <span class="erp-badge bg-gray-100 text-gray-600">N/A</span>
                            @elseif($item->cash_disbursed_at)
                                <span class="erp-badge bg-green-100 text-green-800">Disbursed</span>
                            @else
                                <span class="erp-badge bg-amber-100 text-amber-800">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-10 text-gray-400">No ledger rows match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items && $items->hasPages())
            <div class="px-4 py-3 border-t border-erp-border bank-ledger-no-print">{{ $items->links() }}</div>
        @endif
    </div>
@endif
@endsection
