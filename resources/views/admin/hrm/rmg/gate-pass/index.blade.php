@extends('layouts.admin')
@section('title', 'Gate Passes')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Gate Passes',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.rmg.gate-pass.create') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">New Gate Pass</a>' : '')
        . '<a href="' . route('admin.hrm.rmg.hub') . '" class="erp-btn-secondary ml-2">← Hub</a>',
])
<div class="erp-panel"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Employee</th><th>Date</th><th>Out</th><th>Expected In</th><th>Destination</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($passes as $pass)
<tr>
<td>{{ $pass->employee?->name }}</td>
<td>{{ $pass->pass_date?->format('d M Y') }}</td>
<td>{{ $pass->out_time ?? '—' }}</td>
<td>{{ $pass->expected_in_time ?? '—' }}</td>
<td>{{ $pass->destination ?? '—' }}</td>
<td>{{ $pass->statusLabel() }}</td>
<td class="text-right">
@if($canManage && $pass->status === 'pending')
<form method="POST" action="{{ route('admin.hrm.rmg.gate-pass.approve', $pass) }}" class="inline">@csrf<button type="submit" class="erp-btn-primary !py-1 !px-2 text-[10px]">Approve</button></form>
<form method="POST" action="{{ route('admin.hrm.rmg.gate-pass.reject', $pass) }}" class="inline ml-1" data-confirm="Reject this gate pass?">@csrf<button type="submit" class="erp-btn-secondary !py-1 !px-2 text-[10px] !text-red-600">Reject</button></form>
@endIf
</td></tr>
@empty<tr><td colspan="7" class="text-center py-8 text-gray-400">No gate passes yet.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $passes->links() }}</div></div>
@endsection
