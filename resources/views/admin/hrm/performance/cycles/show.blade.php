@extends('layouts.admin')

@section('title', 'Cycle #' . $cycle->id)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.performance.cycles.index') }}" class="hover:text-brand">Cycles</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">#{{ $cycle->id }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $cycle->name,
    'subtitle' => $cycle->cycleTypeLabel() . ' · ' . $cycle->factory?->name,
])

<div class="flex flex-wrap items-center justify-end gap-2 mb-4">
    <a href="{{ route('admin.hrm.performance.cycles.index') }}" class="erp-btn-secondary">← Back</a>
    <a href="{{ route('admin.hrm.performance.reviews.index', ['cycle_id' => $cycle->id]) }}" class="erp-btn-primary !py-2 !px-4 text-xs">View Reviews</a>
    @if($canManage && $cycle->isOpen())
        <form method="POST" action="{{ route('admin.hrm.performance.cycles.close', $cycle) }}" data-confirm="Close this cycle?">
            @csrf
            <button type="submit" class="erp-btn-secondary !py-2 !px-4 text-xs">Close Cycle</button>
        </form>
    @endif
    @if($cycle->cycle_type === 'mid_year_6m')
        <a href="{{ route('admin.hrm.performance.bonus-runs.create', ['cycle_id' => $cycle->id]) }}" class="erp-btn-secondary !py-2 !px-4 text-xs">Create Bonus Run</a>
    @endif
    @if($cycle->cycle_type === 'annual_12m')
        <a href="{{ route('admin.hrm.performance.increment-runs.create', ['cycle_id' => $cycle->id]) }}" class="erp-btn-secondary !py-2 !px-4 text-xs">Create Increment Run</a>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Cycle Details</h2></div>
            <div class="erp-panel-body grid grid-cols-2 gap-4 text-sm">
                <div><p class="text-[10px] text-gray-400 uppercase">Period</p><p>{{ $cycle->period_from->format('d M Y') }} – {{ $cycle->period_to->format('d M Y') }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Status</p><p>{{ $cycle->statusLabel() }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Template</p><p>{{ $cycle->template?->name ?? '—' }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Reviews Generated</p><p class="font-bold text-brand">{{ $cycle->review_count }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Opened By</p><p>{{ $cycle->openedByUser?->name ?? '—' }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Opened At</p><p>@portalDateTime($cycle->opened_at)</p></div>
            </div>
        </div>

        @if($cycle->reviews->isNotEmpty())
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Recent Reviews</h2></div>
                <div class="overflow-x-auto">
                    <table class="erp-table text-sm">
                        <thead><tr><th>Employee</th><th>Period</th><th>Score</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            @foreach($cycle->reviews->take(20) as $review)
                                <tr>
                                    <td>{{ $review->employee?->employee_code }} — {{ $review->employee?->name }}</td>
                                    <td class="text-xs">{{ $review->period_from->format('d M') }} – {{ $review->period_to->format('d M Y') }}</td>
                                    <td>{{ $review->overall_score !== null ? number_format($review->overall_score, 1) . '%' : '—' }}</td>
                                    <td><span class="erp-badge bg-gray-100 text-gray-600 text-[10px]">{{ $review->statusLabel() }}</span></td>
                                    <td class="text-right">
                                        @include('partials.erp.table-actions', [
                                            'viewUrl' => route('admin.hrm.performance.reviews.show', $review),
                                        ])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    @if($cycle->notes)
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Notes</h2></div>
            <div class="erp-panel-body text-sm text-gray-600">{{ $cycle->notes }}</div>
        </div>
    @endif
</div>
@endsection
