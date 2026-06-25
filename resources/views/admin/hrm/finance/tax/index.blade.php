@extends('layouts.admin')
@section('title', 'Income Tax (TDS)')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Income Tax (TDS)',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.finance.tax.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">New Assessment Year</a>' : '')
        . '<a href="' . route('admin.hrm.finance.hub') . '" class="erp-btn-secondary ml-2">← Hub</a>',
])
<div class="erp-panel mb-4"><div class="erp-panel-body">
<form method="GET" class="flex flex-wrap gap-3 items-end">
    @if(count($factories) > 1)
    <div><label class="erp-form-label">Factory</label>
    <select name="factory_id" class="erp-input !text-xs"><option value="">All</option>
    @foreach($factories as $id=>$n)<option value="{{ $id }}" {{ (string)($filters['factory_id']??'')===(string)$id?'selected':'' }}>{{ $n }}</option>@endforeach</select></div>
    @endif
    <button type="submit" class="erp-btn-secondary">Filter</button>
</form></div></div>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
<div class="erp-panel"><div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase text-gray-600">Assessment Years</h2></div>
<div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Factory</th><th>Year</th><th>Period</th><th>Slabs</th><th>Status</th>@if($canManage)<th></th>@endif</tr></thead>
<tbody>@forelse($years as $year)
<tr><td>{{ $year->factory?->name }}</td><td>{{ $year->label }}</td>
<td>{{ $year->start_date->format('d M Y') }} – {{ $year->end_date->format('d M Y') }}</td>
<td>{{ $year->slabs_count }}</td>
<td><span class="erp-badge {{ $year->is_active?'bg-green-100 text-green-800':'bg-gray-100 text-gray-600' }}">{{ $year->is_active?'Active':'Inactive' }}</span></td>
@if($canManage)
<td class="text-right">@include('partials.erp.table-actions', ['editUrl' => route('admin.hrm.finance.tax.edit', $year)])</td>
@endif
</tr>
@empty<tr><td colspan="{{ $canManage ? 6 : 5 }}" class="text-center py-8 text-gray-400">No tax years configured.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $years->links('pagination::tailwind', ['paginator' => $years]) }}</div></div>
<div class="erp-panel"><div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase text-gray-600">TDS Ledger</h2></div>
<div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Employee</th><th>Period</th><th>Taxable</th><th>TDS</th></tr></thead>
<tbody>@forelse($ledgers as $row)
<tr><td>{{ $row->employee?->name }}<br><code class="text-[10px] text-gray-400">{{ $row->employee?->employee_code }}</code></td>
<td>{{ $row->year }}-{{ str_pad($row->month,2,'0',STR_PAD_LEFT) }}</td>
<td>৳{{ number_format($row->taxable_income,2) }}</td><td>৳{{ number_format($row->tds_amount,2) }}</td></tr>
@empty<tr><td colspan="4" class="text-center py-8 text-gray-400">No TDS entries yet — run payroll after configuring tax year.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $ledgers->links('pagination::tailwind', ['paginator' => $ledgers]) }}</div></div>
</div>

<div class="erp-panel mt-4">
    <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase text-gray-600">TDS Certificate (PDF)</h2></div>
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.finance.tax.certificate') }}" class="flex flex-wrap gap-3 items-end">
            <div class="erp-filter-field-grow">
                <label class="erp-form-label">Employee</label>
                <select name="employee_id" class="erp-input !text-xs" required>
                    <option value="">Select employee</option>
                    @foreach($employees as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="erp-filter-field">
                <label class="erp-form-label">Assessment Year</label>
                <select name="tax_year_id" class="erp-input !text-xs" required>
                    <option value="">Select year</option>
                    @foreach($allYears as $year)
                        <option value="{{ $year->id }}">{{ $year->label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary">Preview</button>
            <button type="submit" name="download" value="1" class="erp-btn-primary !py-1.5 !px-3 text-xs">Save as PDF</button>
        </form>
        <p class="mt-2 text-[11px] text-gray-400">Generates a printable TDS certificate from payroll ledger entries.</p>
    </div>
</div>

<div class="erp-panel mt-4">
    <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase text-gray-600">Annual TDS Export (CSV)</h2></div>
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.finance.tax.export-annual') }}" class="flex flex-wrap gap-3 items-end">
            <div class="erp-filter-field">
                <label class="erp-form-label">Factory</label>
                <select name="factory_id" class="erp-input !text-xs" required>
                    <option value="">Select</option>
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" {{ (string)($filters['factory_id']??'')===(string)$id?'selected':'' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="erp-filter-field-grow">
                <label class="erp-form-label">Assessment Year</label>
                <select name="tax_year_id" class="erp-input !text-xs" required>
                    <option value="">Select year</option>
                    @foreach($allYears as $year)
                        <option value="{{ $year->id }}">{{ $year->label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-primary !py-1.5 !px-3 text-xs">Download CSV</button>
        </form>
        <p class="mt-2 text-[11px] text-gray-400">Employee-wise total taxable income and TDS for the full assessment year.</p>
    </div>
</div>
@endsection
