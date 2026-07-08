@extends('layouts.admin')

@section('title', 'Disbursement — ' . $period->periodLabel())

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Salary Disbursement',
    'subtitle' => $period->periodLabel() . ' · ' . ($period->factory?->name ?? ''),
    'actions' => '<div class="flex flex-wrap gap-2">'
        . '<a href="' . route('admin.hrm.salary.process.show', $period) . '" class="erp-btn-secondary">← Period</a>'
        . ($period->isFrozen()
            ? '<a href="' . route('admin.hrm.salary.close.cash-list', $period) . '" class="erp-btn-secondary">Cash CSV</a>'
            : '')
        . '</div>',
])

@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'close'])

@if($period->status === 'calculated' && $pendingCash > 0)
<div class="mb-4 rounded-sm border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
    <strong>{{ $pendingCash }}</strong> employee(s) still need cash disbursed mark before salary close.
</div>
@elseif($period->status === 'calculated')
<div class="mb-4 rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
    All cash disbursements marked. Period is ready to close.
</div>
@endif

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="erp-panel"><div class="erp-panel-body text-center"><p class="text-[11px] text-gray-500 uppercase">Net Total</p><p class="text-lg font-bold tabular-nums">৳{{ number_format((float) ($totals->net_total ?? 0), 2) }}</p></div></div>
    <div class="erp-panel"><div class="erp-panel-body text-center"><p class="text-[11px] text-gray-500 uppercase">Bank</p><p class="text-lg font-bold tabular-nums text-blue-700">৳{{ number_format((float) ($totals->bank_total ?? 0), 2) }}</p></div></div>
    <div class="erp-panel"><div class="erp-panel-body text-center"><p class="text-[11px] text-gray-500 uppercase">Cash</p><p class="text-lg font-bold tabular-nums text-orange-700">৳{{ number_format((float) ($totals->cash_total ?? 0), 2) }}</p></div></div>
    <div class="erp-panel"><div class="erp-panel-body text-center"><p class="text-[11px] text-gray-500 uppercase">Cash Pending</p><p class="text-lg font-bold tabular-nums {{ ($totals->cash_pending ?? 0) > 0 ? 'text-red-600' : 'text-emerald-700' }}">{{ (int) ($totals->cash_pending ?? 0) }}</p></div></div>
</div>

<div class="erp-panel mb-4">
    <div class="erp-panel-body flex flex-wrap gap-3 items-end justify-between">
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <div>
                <label class="erp-form-label">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="erp-input !text-xs" placeholder="Code or name">
            </div>
            <div>
                <label class="erp-form-label">Cash status</label>
                <select name="cash_status" class="erp-input !text-xs">
                    <option value="">All</option>
                    <option value="pending" @selected(($filters['cash_status'] ?? '') === 'pending')>Pending</option>
                    <option value="done" @selected(($filters['cash_status'] ?? '') === 'done')>Done / N/A</option>
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary !py-2">Filter</button>
        </form>
        @if($canManage && ! $period->isFrozen() && ($totals->cash_pending ?? 0) > 0)
        <form method="POST" action="{{ route('admin.hrm.salary.disbursement.mark-all-cash', $period) }}"
              data-confirm="Mark all pending cash as disbursed?"
              data-confirm-variant="warning">
            @csrf
            <button type="submit" class="erp-btn-primary !py-2">Mark All Cash Disbursed</button>
        </form>
        @endif
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Bank</th>
                <th class="text-right">Net</th>
                <th class="text-right">Bank</th>
                <th class="text-right">Cash</th>
                <th>Cash Status</th>
                @if($canManage && ! $period->isFrozen())
                    <th class="text-right">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                @php
                    $canEdit = $canManage && ! $period->isFrozen();
                    $hasCash = (float) $item->cash_pay_amount > 0;
                @endphp
                <tr>
                    <td>
                        <p class="font-medium text-sm">{{ $item->employee?->name }}</p>
                        <p class="text-[11px] text-gray-500 font-mono">{{ $item->employee?->employee_code }}</p>
                    </td>
                    <td class="text-xs">{{ $item->salaryBank?->displayName() ?? '—' }}</td>
                    <td class="text-right tabular-nums">৳{{ number_format((float) $item->net_pay, 2) }}</td>
                    @if($canEdit)
                    <td colspan="2">
                        <form method="POST" action="{{ route('admin.hrm.salary.disbursement.update-split', [$period, $item]) }}" class="flex flex-wrap gap-2 justify-end items-center">
                            @csrf
                            @method('PUT')
                            <label class="text-[11px] text-gray-500">Bank</label>
                            <input type="number" step="0.01" min="0" name="bank_pay_amount" value="{{ number_format((float) $item->bank_pay_amount, 2, '.', '') }}" class="erp-input !text-xs w-28 text-right tabular-nums" required>
                            <label class="text-[11px] text-gray-500">Cash</label>
                            <input type="number" step="0.01" min="0" name="cash_pay_amount" value="{{ number_format((float) $item->cash_pay_amount, 2, '.', '') }}" class="erp-input !text-xs w-28 text-right tabular-nums" required>
                            <button type="submit" class="erp-btn-sm-secondary">Save</button>
                        </form>
                        @if($item->disbursement_override)
                            <p class="text-[10px] text-amber-600 text-right mt-1">Manual override</p>
                        @endif
                    </td>
                    @else
                    <td class="text-right tabular-nums">৳{{ number_format((float) $item->bank_pay_amount, 2) }}</td>
                    <td class="text-right tabular-nums">৳{{ number_format((float) $item->cash_pay_amount, 2) }}</td>
                    @endif
                    <td>
                        @if(! $hasCash)
                            <span class="erp-badge bg-gray-100 text-gray-600">N/A</span>
                        @elseif($item->cash_disbursed_at)
                            <span class="erp-badge bg-green-100 text-green-800">Disbursed</span>
                            <p class="text-[10px] text-gray-400 mt-0.5">@portalDateTime($item->cash_disbursed_at)</p>
                        @else
                            <span class="erp-badge bg-amber-100 text-amber-800">Pending</span>
                        @endif
                    </td>
                    @if($canEdit)
                    <td class="text-right">
                        @if($hasCash && ! $item->cash_disbursed_at)
                        <form method="POST" action="{{ route('admin.hrm.salary.disbursement.mark-cash', [$period, $item]) }}" class="inline"
                              data-confirm="Mark cash as disbursed for {{ $item->employee?->name }} ({{ $item->employee?->employee_code }})?"
                              data-confirm-variant="warning"
                              data-confirm-ok="Yes, mark disbursed">
                            @csrf
                            <button type="submit" class="erp-btn-primary !py-1 !px-2 text-[11px]">Mark Disbursed</button>
                        </form>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ ($canManage && ! $period->isFrozen()) ? 7 : 6 }}" class="text-center py-10 text-gray-400">No payroll items.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($items->hasPages())
        <div class="px-4 py-3 border-t border-erp-border">{{ $items->links() }}</div>
    @endif
</div>
@endsection
