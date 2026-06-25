@extends('layouts.employee')

@section('title', 'Attendance')
@section('page-title', 'My Attendance')
@section('page-subtitle', $employee->employee_code)

@section('content')
<div class="space-y-5">

    <a href="{{ route('employee.attendance.check-in') }}"
       class="emp-card flex items-center gap-4 border-2 border-brand/20 bg-brand/5 p-4 active:scale-[0.99]">
        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand text-2xl text-white">✓</span>
        <div class="flex-1">
            <p class="text-base font-bold text-gray-900">Check In / Out</p>
            <p class="text-xs text-gray-500">Mobile GPS + selfie · or scan gate QR</p>
        </div>
        <span class="emp-btn-sm">Open</span>
    </a>

    <div class="grid grid-cols-2 gap-2.5">
        @foreach([
            'present'  => ['label' => 'Present', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-800'],
            'late'     => ['label' => 'Late', 'bg' => 'bg-amber-50', 'text' => 'text-amber-800'],
            'absent'   => ['label' => 'Absent', 'bg' => 'bg-red-50', 'text' => 'text-red-700'],
            'half_day' => ['label' => 'Half Day', 'bg' => 'bg-orange-50', 'text' => 'text-orange-800'],
        ] as $key => $style)
            <div class="emp-stat {{ $style['bg'] }} {{ $style['text'] }}">
                <p class="emp-stat-value">{{ $summary[$key] ?? 0 }}</p>
                <p class="emp-stat-label">{{ $style['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div>
        <p class="emp-section-title">This Month Summary</p>
        <p class="mb-3 px-0.5 text-xs text-gray-400">Counts for {{ now()->format('F Y') }}</p>
    </div>

    <div>
        <p class="emp-section-title">History</p>
        <div class="emp-card overflow-hidden">
            @forelse($logs as $log)
                @php
                    $badgeClass = match($log->status) {
                        'present' => 'bg-emerald-100 text-emerald-700',
                        'late' => 'bg-amber-100 text-amber-700',
                        'absent' => 'bg-red-100 text-red-700',
                        'half_day' => 'bg-orange-100 text-orange-700',
                        'off_day' => 'bg-gray-100 text-gray-500',
                        'holiday' => 'bg-blue-100 text-blue-700',
                        default => 'bg-gray-100 text-gray-600',
                    };
                    $label = $log->status === 'half_day' ? $log->displayStatusLabel() : $log->lateStatusLabel();
                @endphp
                <div class="emp-list-item {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl bg-gray-50">
                        <span class="text-[10px] font-bold text-gray-400">{{ $log->attendance_date->format('M') }}</span>
                        <span class="text-base font-bold tabular-nums text-gray-800">{{ $log->attendance_date->format('d') }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900">{{ $log->attendance_date->format('D, d M Y') }}</p>
                        <p class="text-xs text-gray-500 tabular-nums">
                            {{ $log->check_in?->format('H:i') ?? '—' }} – {{ $log->check_out?->format('H:i') ?? '—' }}
                            @if($log->work_minutes > 0) · {{ $log->workHoursFormatted() }} @endif
                        </p>
                    </div>
                    <span class="emp-badge {{ $badgeClass }}">{{ $label }}</span>
                </div>
            @empty
                <p class="px-4 py-10 text-center text-sm text-gray-400">No attendance records yet.</p>
            @endforelse
        </div>
        @if($logs->hasPages())
            <div class="mt-3 text-center">{{ $logs->links() }}</div>
        @endif
    </div>

    <a href="{{ route('employee.late-acceptance.index') }}" class="emp-card flex items-center gap-3 p-4 active:bg-gray-50">
        <span class="emp-quick-icon bg-orange-50 text-orange-600 !h-10 !w-10 !rounded-xl">
            @include('employee.partials.tab-icon', ['icon' => 'late'])
        </span>
        <div class="flex-1">
            <p class="text-sm font-semibold text-gray-900">Late Acceptance</p>
            <p class="text-xs text-gray-500">Apply to forgive a late day</p>
        </div>
        <span class="emp-btn-sm-secondary">Open</span>
    </a>
</div>
@endsection
