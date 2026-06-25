@extends('layouts.employee')

@section('title', 'Late Acceptance')
@section('page-title', 'Late Acceptance')
@section('page-subtitle', $employee->employee_code)

@section('header-action')
    @if(! $employee->late_acceptance_enabled)
        <a href="{{ route('employee.late-acceptance.apply') }}" class="rounded-xl bg-white/15 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur transition hover:bg-white/25">
            + Apply
        </a>
    @else
        <span class="emp-badge bg-blue-400/20 text-blue-100">Privilege</span>
    @endif
@endsection

@section('content')
<div class="space-y-5">

    @if($employee->late_acceptance_enabled)
        <div class="emp-card-padded flex items-start gap-3 bg-blue-50/80">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                @include('employee.partials.tab-icon', ['icon' => 'late'])
            </span>
            <div>
                <p class="text-sm font-semibold text-blue-900">Standing privilege active</p>
                <p class="mt-0.5 text-xs text-blue-700/80">Late days are automatically forgiven for salary deduction.</p>
            </div>
        </div>
    @endif

    <div>
        <p class="emp-section-title">My Applications</p>
        <div class="emp-card overflow-hidden">
            @forelse($applications as $application)
                @php
                    $badgeClass = match($application->status) {
                        'pending' => 'bg-amber-100 text-amber-700',
                        'approved' => 'bg-emerald-100 text-emerald-700',
                        'rejected' => 'bg-red-100 text-red-700',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <div class="emp-list-item {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl bg-orange-50">
                        <span class="text-[10px] font-bold text-orange-400">{{ $application->attendance_date->format('M') }}</span>
                        <span class="text-base font-bold tabular-nums text-orange-700">{{ $application->attendance_date->format('d') }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900">{{ $application->attendance_date->format('d M Y') }}</p>
                        <p class="truncate text-xs text-gray-500">{{ $application->reason }}</p>
                    </div>
                    <span class="emp-badge {{ $badgeClass }}">{{ $application->statusLabel() }}</span>
                </div>
            @empty
                <div class="px-6 py-10 text-center">
                    <p class="text-sm text-gray-500">No applications yet.</p>
                    @if(! $employee->late_acceptance_enabled)
                        <a href="{{ route('employee.late-acceptance.apply') }}" class="mt-3 inline-block emp-btn-sm">Apply now</a>
                    @endif
                </div>
            @endforelse
        </div>
        @if($applications->hasPages())
            <div class="mt-3 text-center">{{ $applications->links() }}</div>
        @endif
    </div>
</div>
@endsection
