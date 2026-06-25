@extends('layouts.admin')
@section('title', 'Maternity Transactions')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Maternity Transactions', 'actions' => ($canManage?'<a href="'.route('admin.hrm.leave.maternity-transactions.create').'" class="erp-btn-primary !py-2 !px-4 text-xs">Add Case</a>':'')])
<div class="erp-panel"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Employee</th><th>Start</th><th>End</th><th>Paid Wks</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($transactions as $tx)
<tr><td>{{ $tx->employee?->employee_code }} — {{ $tx->employee?->name }}</td><td>{{ $tx->start_date->format('d M Y') }}</td><td>{{ $tx->end_date->format('d M Y') }}</td><td>{{ $tx->paid_weeks }}</td><td>{{ $tx->statusLabel() }}</td>
<td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.hrm.leave.maternity-transactions.show', $tx)])</td></tr>
@empty<tr><td colspan="6" class="text-center py-8 text-gray-400">No maternity transactions.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $transactions->links() }}</div></div>
@endsection
