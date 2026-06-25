@extends('layouts.admin')
@section('title', 'Provident Fund')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Provident Fund Accounts',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.finance.pf.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Open PF Account</a>' : '')
        . '<a href="' . route('admin.hrm.finance.pf.employer-report') . '" class="erp-btn-secondary ml-2 !py-2 !px-4 text-xs">Employer Report</a>'
        . '<a href="' . route('admin.hrm.finance.hub') . '" class="erp-btn-secondary ml-2">← Hub</a>',
])
<div class="erp-panel"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Employee</th><th>EE Rate</th><th>ER Rate</th><th>Balance</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($accounts as $account)
<tr><td>{{ $account->employee?->name }}<br><code class="text-[10px] text-gray-400">{{ $account->employee?->employee_code }}</code></td>
<td>{{ number_format($account->employee_rate_pct,2) }}%</td><td>{{ number_format($account->employer_rate_pct,2) }}%</td>
<td>৳{{ number_format($account->balance,2) }}</td>
<td><span class="erp-badge {{ $account->is_active?'bg-green-100 text-green-800':'bg-gray-100 text-gray-600' }}">{{ $account->is_active?'Active':'Inactive' }}</span></td>
<td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.hrm.finance.pf.show', $account)])</td></tr>
@empty<tr><td colspan="6" class="text-center py-8 text-gray-400">No PF accounts yet.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $accounts->links() }}</div></div>
@endsection
