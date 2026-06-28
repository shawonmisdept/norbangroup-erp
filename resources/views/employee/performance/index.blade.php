@extends('layouts.employee')

@section('title', 'Performance')
@section('page-title', 'Performance Reviews')
@section('page-subtitle', $employee->employee_code)

@section('content')
<div class="space-y-5">
    @if($latestApproved)
        <div class="emp-card-padded">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Latest Approved Score</p>
            <p class="mt-1 text-3xl font-bold tabular-nums {{ $latestApproved->passedMinimumScore() ? 'text-emerald-600' : 'text-amber-600' }}">
                {{ $latestApproved->overall_score !== null ? number_format($latestApproved->overall_score, 1) . '%' : '—' }}
            </p>
            <p class="mt-1 text-xs text-gray-500">{{ $latestApproved->cycleTypeLabel() }} · {{ $latestApproved->period_from->format('M Y') }} – {{ $latestApproved->period_to->format('M Y') }}</p>
            <a href="{{ route('employee.performance.show', $latestApproved) }}" class="emp-btn-sm-secondary mt-3 inline-block">View details</a>
        </div>
    @endif

    <div>
        <p class="emp-section-title">Review History</p>
        <div class="emp-card overflow-hidden">
            @forelse($reviews as $review)
                @php
                    $badgeClass = match($review->status) {
                        'pending_rating', 'pending_hr' => 'bg-amber-100 text-amber-700',
                        'approved' => 'bg-emerald-100 text-emerald-700',
                        'rejected' => 'bg-red-100 text-red-700',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <a href="{{ route('employee.performance.show', $review) }}"
                   class="emp-list-item block {{ !$loop->last ? 'border-b border-gray-100' : '' }} active:bg-gray-50">
                    <div class="flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-xl bg-violet-50 text-center">
                        <span class="text-[10px] font-bold leading-none text-violet-400">{{ $review->period_to->format('M') }}</span>
                        <span class="text-sm font-bold leading-none tabular-nums text-violet-800">{{ $review->period_to->format('y') }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $review->cycleTypeLabel() }}</p>
                        <p class="text-xs text-gray-500">{{ $review->period_from->format('d M Y') }} – {{ $review->period_to->format('d M Y') }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        @if($review->status === 'approved' && $review->overall_score !== null)
                            <p class="text-sm font-bold tabular-nums text-gray-900">{{ number_format($review->overall_score, 1) }}%</p>
                        @elseif($review->status === 'rejected')
                            <p class="text-xs text-red-600">Not approved</p>
                        @else
                            <p class="text-xs text-amber-600">In progress</p>
                        @endif
                        <span class="emp-badge {{ $badgeClass }} mt-1">{{ $review->statusLabel() }}</span>
                    </div>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-gray-400">No performance reviews yet.</p>
            @endforelse
        </div>
    </div>

    {{ $reviews->links() }}
</div>
@endsection
