@extends('layouts.admin')
@section('title', 'PF Employer Report')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'PF Employer Contribution Report',
    'subtitle' => 'Monthly employer & employee PF breakdown from payroll',
    'actions' => '<a href="' . route('admin.hrm.finance.pf.index') . '" class="erp-btn-secondary">← PF Accounts</a>',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.finance.pf.employer-report') }}" class="erp-filter-bar">
            <div class="erp-filter-field">
                <label class="erp-form-label">Factory</label>
                <select name="factory_id" class="erp-input !text-xs" required>
                    <option value="">Select</option>
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" {{ $filterFactoryId === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="erp-filter-field">
                <label class="erp-form-label">Year</label>
                <input type="number" name="year" value="{{ $year }}" class="erp-input !text-xs" min="2020" max="2100">
            </div>
            <div class="erp-filter-field">
                <label class="erp-form-label">Month</label>
                <select name="month" class="erp-input !text-xs">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ (int) $month === $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endfor
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary">Load</button>
            @if($filterFactoryId)
                <a href="{{ route('admin.hrm.finance.pf.employer-report.export', ['factory_id' => $filterFactoryId, 'year' => $year, 'month' => $month]) }}"
                   class="erp-btn-primary !py-1.5 !px-3 text-xs">Export CSV</a>
            @endif
        </form>
    </div>
</div>

@if($filterFactoryId)
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
    <div class="erp-panel"><div class="erp-panel-body text-center">
        <p class="text-[10px] uppercase text-gray-400">Total Base</p>
        <p class="text-lg font-bold tabular-nums">৳{{ number_format($totals['base'], 2) }}</p>
    </div></div>
    <div class="erp-panel"><div class="erp-panel-body text-center">
        <p class="text-[10px] uppercase text-gray-400">Employee PF</p>
        <p class="text-lg font-bold tabular-nums text-blue-700">৳{{ number_format($totals['employee'], 2) }}</p>
    </div></div>
    <div class="erp-panel"><div class="erp-panel-body text-center">
        <p class="text-[10px] uppercase text-gray-400">Employer PF</p>
        <p class="text-lg font-bold tabular-nums text-brand">৳{{ number_format($totals['employer'], 2) }}</p>
    </div></div>
</div>

<div class="erp-panel overflow-hidden">
    <table class="erp-table w-full text-xs">
        <thead>
            <tr>
                <th>Employee</th>
                <th class="text-right">Base</th>
                <th class="text-right">Employee PF</th>
                <th class="text-right">Employer PF</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <span class="font-mono text-[10px] text-gray-400">{{ $row['employee']?->employee_code }}</span>
                        <span class="block">{{ $row['employee']?->name }}</span>
                    </td>
                    <td class="text-right tabular-nums">৳{{ number_format($row['base_amount'], 2) }}</td>
                    <td class="text-right tabular-nums">৳{{ number_format($row['employee_amount'], 2) }}</td>
                    <td class="text-right tabular-nums font-medium">৳{{ number_format($row['employer_amount'], 2) }}</td>
                    <td class="text-right tabular-nums">৳{{ number_format($row['employee_amount'] + $row['employer_amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-8 text-gray-400">No PF contributions for this period — run payroll first.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@else
    <div class="erp-panel"><div class="erp-panel-body text-center text-gray-400 py-8">Select a factory to view the report.</div></div>
@endif
@endsection
