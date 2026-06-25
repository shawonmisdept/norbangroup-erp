@extends('layouts.admin')
@section('title', 'Worker Transfers')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Worker Transfers',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.rmg.worker-transfer.create') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">New Transfer</a>' : '')
        . '<a href="' . route('admin.hrm.rmg.hub') . '" class="erp-btn-secondary ml-2">← Hub</a>',
])
<div class="erp-panel"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Employee</th><th>To Factory</th><th>To Line</th><th>Effective</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($transfers as $transfer)
<tr>
<td>{{ $transfer->employee?->name }}</td>
<td>{{ $transfer->toFactory?->name ?? '—' }}</td>
<td>{{ $transfer->toLine?->name ?? '—' }}</td>
<td>{{ $transfer->effective_date?->format('d M Y') }}</td>
<td>{{ $transfer->statusLabel() }}</td>
<td class="text-right">
@if($canManage && $transfer->status === 'pending')
<form method="POST" action="{{ route('admin.hrm.rmg.worker-transfer.approve', $transfer) }}" class="inline">@csrf<button type="submit" class="erp-btn-primary !py-1 !px-2 text-[10px]">Approve</button></form>
@endIf
</td></tr>
@empty<tr><td colspan="6" class="text-center py-8 text-gray-400">No transfer requests yet.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $transfers->links() }}</div></div>
@endsection
