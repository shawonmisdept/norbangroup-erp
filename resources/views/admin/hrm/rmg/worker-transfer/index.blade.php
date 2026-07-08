@extends('layouts.admin')
@section('title', 'Worker Transfers')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Worker Transfers',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.rmg.worker-transfer.create') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">New Transfer</a>' : '')
        . '<a href="' . route('admin.hrm.rmg.hub') . '" class="erp-btn-secondary ml-2">← Hub</a>',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="erp-filter-bar">
            <div class="erp-filter-field">
                <label class="erp-form-label">Factory</label>
                <select name="factory_id" class="erp-input !text-xs" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" {{ (string)($filters['factory_id'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="erp-filter-field">
                <label class="erp-form-label">Status</label>
                <select name="status" class="erp-input !text-xs" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

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
@if($canManage)
    <div class="erp-table-actions justify-end">
        @if($transfer->status === 'pending')
            <form method="POST" action="{{ route('admin.hrm.rmg.worker-transfer.approve', $transfer) }}" class="inline"
                  data-confirm="Approve worker transfer to {{ $transfer->toFactory?->name ?? 'destination factory' }}?"
                  data-confirm-variant="primary"
                  data-confirm-ok="Yes, approve">@csrf<button type="submit" class="erp-btn-primary !py-1 !px-2 text-[10px]">Approve</button></form>
            <form method="POST" action="{{ route('admin.hrm.rmg.worker-transfer.reject', $transfer) }}" class="inline"
                  data-confirm="Reject this transfer?"
                  data-confirm-variant="danger">@csrf<button type="submit" class="erp-btn-secondary !py-1 !px-2 text-[10px] !text-red-600">Reject</button></form>
        @endif
        @include('partials.erp.table-actions', [
            'editUrl' => $transfer->status === 'pending' ? route('admin.hrm.rmg.worker-transfer.edit', $transfer) : null,
            'destroyUrl' => in_array($transfer->status, ['pending', 'rejected'], true) ? route('admin.hrm.rmg.worker-transfer.destroy', $transfer) : null,
            'destroyConfirm' => 'Delete this transfer request?',
        ])
    </div>
@endif
</td></tr>
@empty<tr><td colspan="6" class="text-center py-8 text-gray-400">No transfer requests yet.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $transfers->links() }}</div></div>
@endsection
