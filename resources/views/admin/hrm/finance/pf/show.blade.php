@extends('layouts.admin')
@section('title', 'PF Account')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'PF Account — ' . $account->employee?->name,
    'actions' => '<a href="' . route('admin.hrm.finance.pf.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
    <div class="erp-panel"><div class="erp-panel-body text-center">
        <p class="text-[10px] uppercase text-gray-400">Current Balance</p>
        <p class="text-xl font-bold tabular-nums text-brand">৳{{ number_format($account->balance, 2) }}</p>
    </div></div>
    <div class="erp-panel"><div class="erp-panel-body text-center">
        <p class="text-[10px] uppercase text-gray-400">Total Employee PF</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($totals['employee'], 2) }}</p>
    </div></div>
    <div class="erp-panel"><div class="erp-panel-body text-center">
        <p class="text-[10px] uppercase text-gray-400">Total Employer PF</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($totals['employer'], 2) }}</p>
    </div></div>
</div>

<div class="erp-panel mb-4">
    <div class="erp-panel-body flex flex-wrap gap-4 text-xs">
        <span>Code: <strong>{{ $account->employee?->employee_code }}</strong></span>
        <span>EE Rate: <strong>{{ number_format($account->employee_rate_pct, 2) }}%</strong></span>
        <span>ER Rate: <strong>{{ number_format($account->employer_rate_pct, 2) }}%</strong></span>
        <span>Opened: <strong>{{ $account->opened_at?->format('d M Y') ?? '—' }}</strong></span>
        <span>Status: <strong>{{ $account->is_active ? 'Active' : 'Inactive' }}</strong></span>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase text-gray-600">Contribution History</h2></div>
    <table class="erp-table w-full text-xs">
        <thead>
            <tr>
                <th>Period</th>
                <th class="text-right">Base</th>
                <th class="text-right">Employee</th>
                <th class="text-right">Employer</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($contributions as $row)
                <tr>
                    <td>{{ $row->year }}-{{ str_pad($row->month, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="text-right tabular-nums">৳{{ number_format($row->base_amount, 2) }}</td>
                    <td class="text-right tabular-nums">৳{{ number_format($row->employee_amount, 2) }}</td>
                    <td class="text-right tabular-nums">৳{{ number_format($row->employer_amount, 2) }}</td>
                    <td class="text-right tabular-nums font-medium">৳{{ number_format($row->employee_amount + $row->employer_amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-8 text-gray-400">No contributions yet — run payroll after opening account.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-3">{{ $contributions->links() }}</div>
</div>
@endsection
