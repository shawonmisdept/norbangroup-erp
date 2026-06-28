@extends('layouts.admin')

@section('title', 'Performance Dashboard')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Performance Dashboard</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Performance Dashboard',
    'subtitle' => $period_label,
    'actions' => '<a href="' . route('admin.hrm.performance.hub') . '" class="erp-btn-secondary">Hub</a> <a href="' . route('admin.hrm.performance.reviews.index') . '" class="erp-btn-secondary">Reviews</a>',
])

@include('admin.hrm.performance.partials.unit-scope-notice')
@include('admin.hrm.partials.dashboard-filters', ['routeName' => 'admin.hrm.performance.dashboard'])
@include('admin.hrm.partials.dashboard-kpis', ['kpis' => $kpis, 'columns' => 'grid-cols-2 md:grid-cols-3 xl:grid-cols-6'])

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="xl:col-span-2 space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Review Status</h2></div>
            <div class="erp-panel-body grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($review_statuses as $key => $label)
                    <div class="border border-erp-border rounded-sm p-3 text-center">
                        <p class="text-xl font-bold text-gray-800">{{ $pipeline[$key] ?? 0 }}</p>
                        <p class="text-[10px] uppercase text-gray-500 mt-1">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Recent Reviews</h2></div>
            <div class="erp-panel-body divide-y divide-erp-border">
                @forelse($recent_reviews as $row)
                    <a href="{{ route('admin.hrm.performance.reviews.show', $row) }}" class="flex items-center justify-between py-2 text-sm hover:text-brand">
                        <div>
                            <p class="font-medium">{{ $row->employee?->name }}</p>
                            <p class="text-xs text-gray-500">{{ $row->cycleTypeLabel() }} · {{ $row->statusLabel() }}</p>
                        </div>
                        <span class="text-xs font-bold text-brand">{{ $row->overall_score !== null ? number_format($row->overall_score, 1) . '%' : '—' }}</span>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">No reviews in this period.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Open Cycles</h2></div>
        <div class="erp-panel-body space-y-2">
            @forelse($open_cycles as $row)
                <a href="{{ route('admin.hrm.performance.cycles.show', $row) }}" class="block border border-erp-border rounded-sm p-2 hover:border-brand/40 text-sm">
                    <p class="font-medium">{{ $row->name }}</p>
                    <p class="text-xs text-gray-500">{{ $row->factory?->name }} · {{ $row->cycleTypeLabel() }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-400">No open review cycles.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
