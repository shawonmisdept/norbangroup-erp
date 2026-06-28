@extends('layouts.admin')

@section('title', 'Performance Reviews')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.performance.hub') }}" class="hover:text-brand">Performance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Reviews</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Performance Reviews',
    'subtitle' => 'Reporting person rates → HR approves',
    'actions' => '<a href="' . route('admin.hrm.performance.reviews.export', $filters) . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Export CSV</a>',
])

@include('admin.hrm.performance.partials.unit-scope-notice')

<div class="grid grid-cols-2 gap-3 mb-4 max-w-md">
    <div class="erp-panel"><div class="erp-panel-body"><p class="text-xl font-bold text-amber-600">{{ $pendingRating }}</p><p class="text-xs text-gray-500 uppercase">Pending Rating</p></div></div>
    <div class="erp-panel"><div class="erp-panel-body"><p class="text-xl font-bold text-blue-600">{{ $pendingHr }}</p><p class="text-xs text-gray-500 uppercase">Pending HR</p></div></div>
</div>

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[160px]">
                <label class="erp-form-label">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Employee ID, name…" class="erp-input !text-xs">
            </div>
            @if(count($factories) > 1)
                <div class="w-40">
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" class="erp-input !text-xs">
                        <option value="">All</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ (string) ($filters['factory_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="w-36">
                <label class="erp-form-label">Type</label>
                <select name="cycle_type" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($cycleTypes as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['cycle_type'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="erp-form-label">Status</label>
                <select name="status" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary !py-2 !px-4 text-xs">Filter</button>
            <a href="{{ route('admin.hrm.performance.reviews.index', ['pending_rating' => 1]) }}" class="erp-btn-secondary !py-2 !px-3 text-xs">Pending Rating</a>
            <a href="{{ route('admin.hrm.performance.reviews.index', ['pending_hr' => 1]) }}" class="erp-btn-secondary !py-2 !px-3 text-xs">Pending HR</a>
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
                    <th>Period</th>
                    <th>Reporting To</th>
                    <th>Score</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $row)
                    @php
                        $badge = match($row->status) {
                            'pending_rating' => 'bg-amber-100 text-amber-800',
                            'pending_hr' => 'bg-blue-100 text-blue-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'blocked', 'draft' => 'bg-red-100 text-red-700',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <tr>
                        <td>
                            <p class="font-medium">{{ $row->employee?->name }}</p>
                            <code class="text-[10px] text-gray-400">{{ $row->employee?->employee_code }}</code>
                        </td>
                        <td class="text-xs">{{ $row->cycleTypeLabel() }}</td>
                        <td class="text-xs">{{ $row->period_from->format('d M Y') }} – {{ $row->period_to->format('d M Y') }}</td>
                        <td class="text-xs">{{ $row->reportingTo?->name ?? '—' }}</td>
                        <td>{{ $row->overall_score !== null ? number_format($row->overall_score, 1) . '%' : '—' }}</td>
                        <td><span class="erp-badge {{ $badge }} text-[10px]">{{ $row->statusLabel() }}</span></td>
                        <td class="text-right">
                            @include('partials.erp.table-actions', [
                                'viewUrl' => route('admin.hrm.performance.reviews.show', $row),
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-400 py-8">No reviews found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reviews->hasPages())
        <div class="erp-panel-body border-t border-gray-100">{{ $reviews->links() }}</div>
    @endif
</div>
@endsection
