@extends('layouts.employee')

@section('title', $review->cycleTypeLabel())
@section('page-title', $review->cycleTypeLabel())
@section('page-subtitle', $review->period_from->format('d M Y') . ' – ' . $review->period_to->format('d M Y'))
@section('back', route('employee.performance'))

@section('header-action')
    @php
        $badgeClass = match($review->status) {
            'pending_rating', 'pending_hr' => 'bg-amber-400/25 text-amber-100',
            'approved' => 'bg-emerald-400/25 text-emerald-100',
            'rejected' => 'bg-red-400/25 text-red-100',
            default => 'bg-white/15 text-white/80',
        };
    @endphp
    <span class="emp-badge {{ $badgeClass }}">{{ $review->statusLabel() }}</span>
@endsection

@section('content')
<div class="space-y-4">
    @if($showScores)
        <div class="emp-card-padded text-center">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Overall Score</p>
            <p class="mt-1 text-4xl font-bold tabular-nums {{ $review->passedMinimumScore() ? 'text-emerald-600' : 'text-amber-600' }}">
                {{ $review->overall_score !== null ? number_format($review->overall_score, 1) . '%' : '—' }}
            </p>
            <p class="mt-1 text-xs text-gray-500">Minimum pass score: {{ $minimumPass }}%</p>
            @if($review->status === 'rejected')
                <p class="mt-2 text-sm text-red-600">This review was not approved by HR.</p>
            @endif
        </div>

        <div class="emp-card overflow-hidden">
            <div class="border-b border-gray-100 px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Score Breakdown</p>
            </div>
            @foreach($review->scores as $score)
                <div class="flex items-center justify-between gap-3 px-4 py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ $score->label }}</p>
                        <p class="text-[10px] text-gray-400">{{ ucfirst($score->criterion_type) }} · Weight {{ number_format($score->weight, 0) }}%</p>
                    </div>
                    <p class="text-sm font-bold tabular-nums text-gray-800">
                        {{ $score->score !== null ? number_format($score->score, 1) . '%' : '—' }}
                    </p>
                </div>
            @endforeach
        </div>
    @else
        <div class="emp-card-padded text-center">
            <p class="text-sm font-medium text-gray-800">Review in progress</p>
            <p class="mt-1 text-xs text-gray-500">Your scores will appear here after HR approval.</p>
            <p class="mt-3 text-xs text-amber-600">{{ $review->statusLabel() }}</p>
        </div>
    @endif

    <div class="emp-card-padded space-y-2 text-sm">
        <div class="flex justify-between gap-3">
            <span class="text-gray-500">Reporting To</span>
            <span class="font-medium text-gray-900">{{ $review->reportingTo?->name ?? '—' }}</span>
        </div>
        <div class="flex justify-between gap-3">
            <span class="text-gray-500">Designation</span>
            <span class="font-medium text-gray-900">{{ $review->employee->designation?->name ?? '—' }}</span>
        </div>
        @if($review->hr_approved_at)
            <div class="flex justify-between gap-3">
                <span class="text-gray-500">Approved On</span>
                <span class="font-medium text-gray-900">{{ $review->hr_approved_at->format('d M Y') }}</span>
            </div>
        @endif
    </div>
</div>
@endsection
