@extends('layouts.admin')

@section('title', 'Promotions & Demotions')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Promotions</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Promotion & Demotion',
    'subtitle' => 'Designation change, category upgrade/downgrade & salary revision workflow',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.promotions.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs mr-2">New Request</a>' : '')
        . '<a href="' . route('admin.hrm.promotions.export', $filters) . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Export CSV</a>',
])

<div class="grid grid-cols-2 gap-3 mb-4 max-w-xs">
    <div class="erp-panel"><div class="erp-panel-body"><p class="text-xl font-bold text-amber-600">{{ $pendingCount }}</p><p class="text-xs text-gray-500 uppercase">Pending Approval</p></div></div>
</div>

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[180px]">
                <label class="erp-form-label">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Employee ID, name…" class="erp-input !text-xs">
            </div>
            @if(count($factories) > 1)
                <div class="w-44">
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" class="erp-input !text-xs">
                        <option value="">All units</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ (string) ($filters['factory_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="w-36">
                <label class="erp-form-label">Status</label>
                <select name="status" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="erp-form-label">Type</label>
                <select name="movement_type" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($movementTypes as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['movement_type'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary !py-2 !px-4 text-xs">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel">
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Designation Change</th>
                    <th>Effective</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($promotions as $row)
                    @php
                        $statusClass = match ($row->status) {
                            'pending'   => 'bg-amber-100 text-amber-800',
                            'approved'  => 'bg-green-100 text-green-800',
                            'rejected'  => 'bg-red-100 text-red-800',
                            'cancelled' => 'bg-gray-100 text-gray-600',
                            default     => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <tr>
                        <td>
                            <p class="font-medium text-sm">{{ $row->employee?->name }}</p>
                            <p class="text-xs text-gray-500">{{ $row->employee?->employee_code }}</p>
                        </td>
                        <td>{{ $row->movementTypeLabel() }}</td>
                        <td class="text-sm">
                            {{ $row->fromDesignation?->name ?? '—' }}
                            <span class="text-gray-400 mx-1">→</span>
                            {{ $row->toDesignation?->name ?? '—' }}
                        </td>
                        <td class="text-sm">{{ $row->effective_date->format('d M Y') }}</td>
                        <td><span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $statusClass }}">{{ $row->statusLabel() }}</span></td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.hrm.promotions.show', $row) }}" class="text-brand text-xs hover:underline">View</a>
                            @if($canApprove && $row->isPending())
                                <form method="POST" action="{{ route('admin.hrm.promotions.approve', $row) }}" class="inline ml-2"
                                      data-confirm="Approve this request?"
                                      data-confirm-variant="primary"
                                      data-confirm-ok="Yes, approve">
                                    @csrf
                                    <button type="submit" class="text-green-600 text-xs hover:underline">Approve</button>
                                </form>
                                <a href="{{ route('admin.hrm.promotions.show', $row) }}#reject" class="text-red-600 text-xs hover:underline ml-2">Reject</a>
                            @endif
                            @if($canManage && $row->isPending())
                                <form method="POST" action="{{ route('admin.hrm.promotions.cancel', $row) }}" class="inline ml-2" data-confirm="Cancel this request?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-500 text-xs hover:underline">Cancel</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-500 py-8">No promotion or demotion requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($promotions->hasPages())
        <div class="erp-panel-body border-t border-gray-100">{{ $promotions->links() }}</div>
    @endif
</div>
@endsection
