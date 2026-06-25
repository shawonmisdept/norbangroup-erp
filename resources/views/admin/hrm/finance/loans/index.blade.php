@extends('layouts.admin')
@section('title', 'Loans & Advances')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Loans & Advances',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.finance.loans.bulk') . '" class="erp-btn-primary !py-2 !px-4 text-xs mr-2">Bulk Festival Advance</a><a href="' . route('admin.hrm.finance.loans.create') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">New Application</a>' : '')
        . '<a href="' . route('admin.hrm.finance.hub') . '" class="erp-btn-secondary ml-2">← Hub</a>',
])
<div class="erp-panel"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Employee</th><th>Type</th><th>Principal</th><th>Balance</th><th>EMI</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($loans as $loan)
<tr><td>{{ $loan->employee?->name }}</td><td>{{ $loan->loanTypeLabel() }}</td>
<td>৳{{ number_format($loan->principal,2) }}</td><td>৳{{ number_format($loan->balance,2) }}</td>
<td>৳{{ number_format($loan->emi_amount,2) }}</td>
<td>{{ ucfirst($loan->status) }}</td>
<td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.hrm.finance.loans.show', $loan)])</td></tr>
@empty<tr><td colspan="7" class="text-center py-8 text-gray-400">No loan applications yet.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $loans->links() }}</div></div>
@endsection
