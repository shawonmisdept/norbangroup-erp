@extends('layouts.employee')

@section('title', 'Salary Increments')
@section('page-title', 'Salary Increments')
@section('page-subtitle', 'Revision history')
@section('back', route('employee.profile'))

@section('content')
<div class="space-y-4">
    <div class="emp-card overflow-hidden">
        @forelse($increments as $increment)
            <a href="{{ route('employee.career.increments.show', $increment) }}"
               class="block p-4 {{ !$loop->last ? 'border-b border-gray-100' : '' }} active:bg-gray-50">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 tabular-nums">৳{{ number_format((float) $increment->previous_gross, 0) }} → ৳{{ number_format((float) $increment->new_gross, 0) }}</p>
                        <p class="text-[10px] text-gray-500">{{ $increment->applied_at->format('d M Y') }} · {{ $increment->rule?->name ?? 'Direct revision' }}</p>
                    </div>
                    <span class="text-xs font-bold text-emerald-700">+৳{{ number_format((float) $increment->new_gross - (float) $increment->previous_gross, 0) }}</span>
                </div>
            </a>
        @empty
            <p class="px-4 py-10 text-center text-sm text-gray-400">No salary increments recorded yet.</p>
        @endforelse
    </div>
    @if($increments->hasPages())<div class="text-center">{{ $increments->links() }}</div>@endif
</div>
@endsection
