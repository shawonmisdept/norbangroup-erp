@extends('layouts.admin')

@section('title', 'Performance Cycles')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.performance.hub') }}" class="hover:text-brand">Performance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Cycles</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Review Cycles',
    'subtitle' => 'Open probation, January mid-year & annual increment batches',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.performance.cycles.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Open Cycle</a>' : ''),
])

@include('admin.hrm.performance.partials.unit-scope-notice')

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
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
            <div class="w-44">
                <label class="erp-form-label">Cycle Type</label>
                <select name="cycle_type" class="erp-input !text-xs">
                    <option value="">All types</option>
                    @foreach($cycleTypes as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['cycle_type'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-32">
                <label class="erp-form-label">Status</label>
                <select name="status" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
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
                    <th>Name</th>
                    <th>Type</th>
                    <th>Factory</th>
                    <th>Period</th>
                    <th>Reviews</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cycles as $row)
                    <tr>
                        <td class="font-medium">{{ $row->name }}</td>
                        <td>{{ $row->cycleTypeLabel() }}</td>
                        <td>{{ $row->factory?->name ?? '—' }}</td>
                        <td class="text-xs">{{ $row->period_from->format('d M Y') }} – {{ $row->period_to->format('d M Y') }}</td>
                        <td>{{ $row->review_count }}</td>
                        <td><span class="erp-badge {{ $row->status === 'open' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $row->statusLabel() }}</span></td>
                        <td class="text-right">
                            @include('partials.erp.table-actions', [
                                'viewUrl' => route('admin.hrm.performance.cycles.show', $row),
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-400 py-8">No cycles yet. Open a review cycle to get started.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cycles->hasPages())
        <div class="erp-panel-body border-t border-gray-100">{{ $cycles->links() }}</div>
    @endif
</div>
@endsection
