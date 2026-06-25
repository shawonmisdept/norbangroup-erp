@extends('layouts.admin')
@section('title', 'Final Settlement')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Final Settlement (F&F)',
    'subtitle' => 'Full & final on employee exit — gratuity, PF, loans, leave encashment',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.finance.final-settlement.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs mr-2">New Settlement</a>' : '')
        . '<a href="' . route('admin.hrm.finance.hub') . '" class="erp-btn-secondary">← Hub</a>',
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
            @if($filters['factory_id'] ?? null)
                <a href="{{ route('admin.hrm.finance.final-settlement.export', ['factory_id' => $filters['factory_id']]) }}"
                   class="erp-btn-secondary !py-1.5 !px-3 text-xs self-end">Export CSV</a>
            @endif
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <table class="erp-table w-full text-xs">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Separation</th>
                <th>Last Day</th>
                <th class="text-right">Net Payable</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($settlements as $row)
                @php
                    $badge = match($row->status) {
                        'paid' => 'bg-green-100 text-green-800',
                        'approved' => 'bg-blue-100 text-blue-800',
                        'calculated' => 'bg-amber-100 text-amber-800',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <tr>
                    <td>
                        <span class="font-mono text-[10px] text-gray-400">{{ $row->employee?->employee_code }}</span>
                        <span class="block">{{ $row->employee?->name }}</span>
                    </td>
                    <td>{{ ucfirst($row->separation_type) }}</td>
                    <td>{{ $row->last_working_day->format('d M Y') }}</td>
                    <td class="text-right tabular-nums font-medium">৳{{ number_format($row->net_payable, 2) }}</td>
                    <td><span class="erp-badge {{ $badge }}">{{ $row->statusLabel() }}</span></td>
                    <td class="text-right">
                        @include('partials.erp.table-actions', ['viewUrl' => route('admin.hrm.finance.final-settlement.show', $row)])
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-8 text-gray-400">No final settlements yet. Create one when an employee resigns or is terminated.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-3">{{ $settlements->links() }}</div>
</div>
@endsection
