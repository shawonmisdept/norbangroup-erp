@extends('layouts.employee')

@section('title', 'Promotions')
@section('page-title', 'Promotions & Movements')
@section('page-subtitle', 'Career history')
@section('back', route('employee.profile'))

@section('content')
<div class="space-y-4">
    <div class="emp-card overflow-hidden">
        @forelse($promotions as $promotion)
            <a href="{{ route('employee.career.promotions.show', $promotion) }}"
               class="block p-4 {{ !$loop->last ? 'border-b border-gray-100' : '' }} active:bg-gray-50">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $promotion->movementTypeLabel() }}</p>
                        <p class="text-xs text-gray-500">{{ $promotion->fromDesignation?->name ?? '—' }} → {{ $promotion->toDesignation?->name ?? '—' }}</p>
                        <p class="mt-1 text-[10px] text-gray-400">Effective {{ $promotion->effective_date->format('d M Y') }}</p>
                    </div>
                    <span class="emp-badge {{ match($promotion->status) {
                        'approved' => 'bg-emerald-100 text-emerald-700',
                        'rejected' => 'bg-red-100 text-red-700',
                        default => 'bg-amber-100 text-amber-700',
                    } }}">{{ $promotion->statusLabel() }}</span>
                </div>
            </a>
        @empty
            <p class="px-4 py-10 text-center text-sm text-gray-400">No promotion records yet.</p>
        @endforelse
    </div>
    @if($promotions->hasPages())<div class="text-center">{{ $promotions->links() }}</div>@endif
</div>
@endsection
