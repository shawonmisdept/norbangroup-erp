@extends('layouts.employee')

@section('title', 'Home')

@section('hero')
<div class="emp-hero">
    <div class="mb-4">
        @include('partials.portal.brand-logo', [
            'size' => 'sm',
            'variant' => 'employee',
            'showName' => true,
            'subtitle' => 'Employee Self-Service',
        ])
    </div>
    <div class="emp-hero-inner relative flex items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <div class="emp-avatar-ring shrink-0">
                @include('partials.employee-avatar', ['employee' => $employee, 'size' => '56', 'round' => true])
            </div>
            <div class="min-w-0">
                @php
                    $hour = (int) now()->format('G');
                    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
                @endphp
                <p class="text-xs font-medium text-white/60">{{ $greeting }}</p>
                <h1 class="truncate text-lg font-bold">{{ $employee->name }}</h1>
                <p class="truncate text-[11px] text-white/50">{{ $employee->employee_code }} · {{ $employee->shift?->name ?? 'No shift' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-1 shrink-0">
            @include('employee.partials.notification-bell')
            <form method="POST" action="{{ route('employee.logout') }}">
            @csrf
            <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-white/80 backdrop-blur transition hover:bg-white/20" aria-label="Sign out">
                @include('employee.partials.tab-icon', ['icon' => 'logout'])
            </button>
        </form>
        </div>
    </div>

    @if($latestPayslip)
        <a href="{{ route('employee.payslips.show', $latestPayslip) }}"
           class="relative mt-5 block overflow-hidden rounded-2xl bg-white/10 p-4 backdrop-blur transition active:scale-[0.99]">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-white/50">Latest Net Pay</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums">৳{{ number_format((float) $latestPayslip->net_pay, 0) }}</p>
                    <p class="mt-0.5 text-xs text-white/60">{{ $latestPayslip->period->periodLabel() }}</p>
                </div>
                <span class="emp-btn-sm">View</span>
            </div>
        </a>
    @elseif($activeLoan ?? null)
        <a href="{{ route('employee.loans') }}"
           class="relative mt-5 block overflow-hidden rounded-2xl bg-white/10 p-4 backdrop-blur transition active:scale-[0.99]">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-white/50">Loan Balance</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums">৳{{ number_format((float) $activeLoan->balance, 0) }}</p>
                    <p class="mt-0.5 text-xs text-white/60">EMI ৳{{ number_format((float) $activeLoan->emi_amount, 0) }} · {{ $activeLoan->loanTypeLabel() }}</p>
                </div>
                <span class="emp-btn-sm">Details</span>
            </div>
        </a>
    @endif
</div>
@endsection

@section('content')
<div class="space-y-5">

    {{-- This month stats --}}
    <div>
        <p class="emp-section-title">This Month</p>
        <div class="grid grid-cols-3 gap-2.5">
            <div class="emp-stat bg-emerald-50 text-emerald-800">
                <p class="emp-stat-value">{{ $attendanceSummary['present'] }}</p>
                <p class="emp-stat-label">Present</p>
            </div>
            <div class="emp-stat bg-amber-50 text-amber-800">
                <p class="emp-stat-value">{{ $attendanceSummary['late'] }}</p>
                <p class="emp-stat-label">Late</p>
            </div>
            <div class="emp-stat bg-red-50 text-red-700">
                <p class="emp-stat-value">{{ $attendanceSummary['absent'] }}</p>
                <p class="emp-stat-label">Absent</p>
            </div>
        </div>
    </div>

    {{-- Quick actions --}}
    <div>
        <p class="emp-section-title">Quick Actions</p>
        <div class="grid grid-cols-4 sm:grid-cols-5 gap-2.5">
            <a href="{{ route('employee.attendance.check-in') }}" class="emp-quick">
                <span class="emp-quick-icon bg-brand/10 text-brand">
                    @include('employee.partials.tab-icon', ['icon' => 'clock'])
                </span>
                <span class="text-[10px] font-semibold text-gray-700">Check In</span>
            </a>
            <a href="{{ route('employee.attendance') }}" class="emp-quick">
                <span class="emp-quick-icon bg-blue-50 text-blue-600">
                    @include('employee.partials.tab-icon', ['icon' => 'clock'])
                </span>
                <span class="text-[10px] font-semibold text-gray-700">History</span>
            </a>
            <a href="{{ route('employee.leave.apply') }}" class="emp-quick">
                <span class="emp-quick-icon bg-violet-50 text-violet-600">
                    @include('employee.partials.tab-icon', ['icon' => 'calendar'])
                </span>
                <span class="text-[10px] font-semibold text-gray-700">Apply Leave</span>
            </a>
            <a href="{{ route('employee.late-acceptance.index') }}" class="emp-quick">
                <span class="emp-quick-icon bg-orange-50 text-orange-600">
                    @include('employee.partials.tab-icon', ['icon' => 'late'])
                </span>
                <span class="text-[10px] font-semibold text-gray-700">Late Apply</span>
            </a>
            <a href="{{ route('employee.payslips') }}" class="emp-quick">
                <span class="emp-quick-icon bg-emerald-50 text-emerald-600">
                    @include('employee.partials.tab-icon', ['icon' => 'wallet'])
                </span>
                <span class="text-[10px] font-semibold text-gray-700">Payslips</span>
            </a>
            <a href="{{ route('employee.loans') }}" class="emp-quick">
                <span class="emp-quick-icon bg-rose-50 text-rose-600">
                    @include('employee.partials.tab-icon', ['icon' => 'wallet'])
                </span>
                <span class="text-[10px] font-semibold text-gray-700">Loans</span>
            </a>
            <a href="{{ route('employee.roster') }}" class="emp-quick">
                <span class="emp-quick-icon bg-indigo-50 text-indigo-600">
                    @include('employee.partials.tab-icon', ['icon' => 'calendar'])
                </span>
                <span class="text-[10px] font-semibold text-gray-700">Roster</span>
            </a>
            <a href="{{ route('employee.pf') }}" class="emp-quick">
                <span class="emp-quick-icon bg-teal-50 text-teal-600">
                    @include('employee.partials.tab-icon', ['icon' => 'wallet'])
                </span>
                <span class="text-[10px] font-semibold text-gray-700">PF</span>
            </a>
            <a href="{{ route('employee.performance') }}" class="emp-quick">
                <span class="emp-quick-icon bg-violet-50 text-violet-600">
                    @include('employee.partials.tab-icon', ['icon' => 'star'])
                </span>
                <span class="text-[10px] font-semibold text-gray-700">Performance</span>
            </a>
            <a href="{{ route('employee.transport.index') }}" class="emp-quick">
                <span class="emp-quick-icon bg-sky-50 text-sky-600">
                    @include('employee.partials.tab-icon', ['icon' => 'clock'])
                </span>
                <span class="text-[10px] font-semibold text-gray-700">Transport</span>
            </a>
        </div>
    </div>

    @if($pendingLeave > 0)
        <a href="{{ route('employee.leave') }}" class="emp-card flex items-center gap-3 p-4 active:bg-gray-50">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 text-sm font-bold">{{ $pendingLeave }}</span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-900">Pending leave request{{ $pendingLeave > 1 ? 's' : '' }}</p>
                <p class="text-xs text-gray-500">Waiting for approval</p>
            </div>
            <span class="emp-btn-sm-secondary">View</span>
        </a>
    @endif

    @if($pfAccount ?? null)
        <a href="{{ route('employee.pf') }}" class="emp-card flex items-center gap-3 p-4 active:bg-gray-50">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 text-xs font-bold">PF</span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-900">Provident Fund Balance</p>
                <p class="text-xs text-gray-500 tabular-nums">৳{{ number_format((float) $pfAccount->balance, 2) }}</p>
            </div>
            <span class="emp-btn-sm-secondary">Details</span>
        </a>
    @endif

    {{-- Recent attendance --}}
    <div>
        <div class="mb-2.5 flex items-center justify-between px-0.5">
            <p class="emp-section-title !mb-0">Recent Attendance</p>
            <a href="{{ route('employee.attendance') }}" class="emp-btn-sm-secondary">See all</a>
        </div>
        <div class="emp-card overflow-hidden">
            @forelse($recentLogs as $log)
                @php
                    $badgeClass = match($log->status) {
                        'present' => 'bg-emerald-100 text-emerald-700',
                        'late' => 'bg-amber-100 text-amber-700',
                        'absent' => 'bg-red-100 text-red-700',
                        'half_day' => 'bg-orange-100 text-orange-700',
                        'off_day' => 'bg-gray-100 text-gray-500',
                        default => 'bg-gray-100 text-gray-600',
                    };
                    $label = $log->status === 'half_day' ? $log->displayStatusLabel() : $log->lateStatusLabel();
                @endphp
                <div class="emp-list-item {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-xl bg-gray-50 text-center">
                        <span class="text-[10px] font-bold leading-none text-gray-400">{{ $log->attendance_date->format('M') }}</span>
                        <span class="text-sm font-bold leading-none tabular-nums text-gray-800">{{ $log->attendance_date->format('d') }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $log->attendance_date->format('l') }}</p>
                        <p class="text-xs text-gray-500 tabular-nums">
                            {{ $log->check_in?->format('H:i') ?? '—' }} – {{ $log->check_out?->format('H:i') ?? '—' }}
                        </p>
                    </div>
                    <span class="emp-badge {{ $badgeClass }}">{{ $label }}</span>
                </div>
            @empty
                <p class="px-4 py-8 text-center text-sm text-gray-400">No attendance records yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
