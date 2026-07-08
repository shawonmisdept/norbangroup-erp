@extends('layouts.admin')
@section('title', 'Gate Passes')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Gate Passes',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.rmg.gate-pass.create') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">New Gate Pass</a>' : '')
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
<thead><tr><th>Employee</th><th>Date</th><th>Out</th><th>Expected In</th><th>Destination</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($passes as $pass)
<tr>
<td>{{ $pass->employee?->name }}</td>
<td>{{ $pass->pass_date?->format('d M Y') }}</td>
<td>@if($pass->out_time){{ \App\Support\TimeInput::formatForDisplay($pass->out_time) }}@else—@endif</td>
<td>@if($pass->expected_in_time){{ \App\Support\TimeInput::formatForDisplay($pass->expected_in_time) }}@else—@endif</td>
<td>{{ $pass->destination ?? '—' }}</td>
<td>{{ $pass->statusLabel() }}</td>
<td class="text-right">
@if($canManage)
    <div class="erp-table-actions justify-end">
        @if($pass->status === 'pending')
            <form method="POST" action="{{ route('admin.hrm.rmg.gate-pass.approve', $pass) }}" class="inline"
                  data-confirm="Approve this gate pass?"
                  data-confirm-variant="primary"
                  data-confirm-ok="Yes, approve">@csrf<button type="submit" class="erp-btn-primary !py-1 !px-2 text-[10px]">Approve</button></form>
            <form method="POST" action="{{ route('admin.hrm.rmg.gate-pass.reject', $pass) }}" class="inline"
                  data-confirm="Reject this gate pass?"
                  data-confirm-variant="danger">@csrf<button type="submit" class="erp-btn-secondary !py-1 !px-2 text-[10px] !text-red-600">Reject</button></form>
        @endif
        @include('partials.erp.table-actions', [
            'editUrl' => $pass->status === 'pending' ? route('admin.hrm.rmg.gate-pass.edit', $pass) : null,
            'destroyUrl' => in_array($pass->status, ['pending', 'rejected'], true) ? route('admin.hrm.rmg.gate-pass.destroy', $pass) : null,
            'destroyConfirm' => 'Delete this gate pass?',
        ])
    </div>
@endif
</td></tr>
@empty<tr><td colspan="7" class="text-center py-8 text-gray-400">No gate passes yet.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $passes->links() }}</div></div>
@endsection
