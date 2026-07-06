@extends('layouts.employee')

@section('title', 'Increment Detail')
@section('page-title', 'Salary Increment')
@section('page-subtitle', $increment->applied_at->format('d M Y'))
@section('back', route('employee.career.increments'))

@section('content')
<div class="space-y-4">
    <div class="emp-card-padded text-center">
        <p class="text-[10px] uppercase text-gray-400">Gross revision</p>
        <p class="mt-2 text-2xl font-bold tabular-nums text-gray-900">৳{{ number_format((float) $increment->previous_gross, 2) }}</p>
        <p class="text-emerald-600 font-bold">↓</p>
        <p class="text-3xl font-bold tabular-nums text-brand">৳{{ number_format((float) $increment->new_gross, 2) }}</p>
        <p class="mt-2 text-sm text-emerald-700 font-semibold">+৳{{ number_format((float) $increment->new_gross - (float) $increment->previous_gross, 2) }}</p>
    </div>

    <div class="emp-card divide-y divide-gray-100 text-sm">
        <div class="flex justify-between px-4 py-3"><span class="text-gray-500">Applied on</span><span class="font-semibold">@portalDateTime($increment->applied_at)</span></div>
        <div class="flex justify-between px-4 py-3"><span class="text-gray-500">Rule / source</span><span class="font-semibold">{{ $increment->rule?->name ?? 'Direct revision' }}</span></div>
        @if($increment->performanceReview)
            <div class="flex justify-between px-4 py-3"><span class="text-gray-500">Performance review</span><span class="font-semibold">{{ $increment->performanceReview->cycle?->name ?? 'Linked review' }}</span></div>
        @endif
    </div>
</div>
@endsection
