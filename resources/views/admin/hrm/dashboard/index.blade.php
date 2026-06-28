@extends('layouts.admin')

@section('title', 'HRM Dashboard — ' . config('app.name'))

@section('breadcrumbs')
    <span class="text-gray-600 font-medium">HRM</span><span>/</span>
    <span class="text-gray-800 font-medium">Dashboard</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'HRM Dashboard',
    'subtitle' => 'Employee & today\'s attendance overview',
    'actions' => '<span class="text-xs text-gray-500">' . $date_label . '</span>',
])

@if(count($factories) > 1 && ! auth()->user()->factory_id)
<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('admin.hrm.dashboard', ['date' => $filters['date']]) }}"
       class="px-3 py-1.5 text-[11px] font-semibold rounded-sm border transition-colors {{ empty($filters['factory_id']) ? 'bg-brand text-white border-brand' : 'bg-white text-gray-600 border-erp-border hover:border-brand/40' }}">
        All Companies
    </a>
    @foreach($factories as $id => $name)
        <a href="{{ route('admin.hrm.dashboard', ['factory_id' => $id, 'date' => $filters['date']]) }}"
           class="px-3 py-1.5 text-[11px] font-semibold rounded-sm border transition-colors {{ (int) ($filters['factory_id'] ?? 0) === (int) $id ? 'bg-brand text-white border-brand' : 'bg-white text-gray-600 border-erp-border hover:border-brand/40' }}">
            {{ $name }}
        </a>
    @endforeach
</div>
@endif

<div class="erp-panel mb-4">
    <form method="GET" action="{{ route('admin.hrm.dashboard') }}" class="erp-panel-body erp-filter-bar">
        @if($filters['factory_id'])
            <input type="hidden" name="factory_id" value="{{ $filters['factory_id'] }}">
        @endif
        <div>
            <label class="erp-form-label">Date</label>
            <input type="date" name="date" value="{{ $filters['date'] }}" class="erp-input !text-xs">
        </div>
        <div class="flex items-end">
            <button type="submit" class="erp-btn-primary">Apply</button>
        </div>
    </form>
</div>

<h2 class="text-sm font-semibold text-gray-800 mb-3">Employee Data</h2>
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3 mb-6">
    @foreach([
        ['Total Employee', $employee_stats['total'], 'border-brand/20 bg-brand/5', 'text-brand'],
        ['Male Employee', $employee_stats['male'], 'border-emerald-200 bg-emerald-50/60', 'text-emerald-700'],
        ['Female Employee', $employee_stats['female'], 'border-orange-200 bg-orange-50/60', 'text-orange-700'],
        ['Other Gender', $employee_stats['other'], 'border-pink-200 bg-pink-50/60', 'text-pink-700'],
        ['Separated', $employee_stats['separated'], 'border-amber-200 bg-amber-50/60', 'text-amber-700'],
    ] as [$label, $value, $panel, $text])
        <div class="erp-kpi {{ $panel }}">
            <p class="erp-kpi-value {{ $text }}">{{ number_format($value) }}</p>
            <p class="erp-kpi-label {{ $text }}">{{ $label }}</p>
        </div>
    @endforeach
</div>

@if($finance_stats ?? null)
<h2 class="text-sm font-semibold text-gray-800 mb-3">Finance Overview — {{ now()->format('F Y') }}</h2>
<div class="grid grid-cols-2 lg:grid-cols-6 gap-3 mb-6">
    <a href="{{ route('admin.hrm.finance.loans.index', ['status' => 'pending']) }}" class="erp-kpi border-amber-200 bg-amber-50/60 hover:border-brand/40 transition-all block">
        <p class="erp-kpi-value text-amber-700">{{ number_format($finance_stats['pending_loans']) }}</p>
        <p class="erp-kpi-label text-amber-700">Pending Loans</p>
    </a>
    <a href="{{ route('admin.hrm.finance.loans.index', ['status' => 'active']) }}" class="erp-kpi border-orange-200 bg-orange-50/60 hover:border-brand/40 transition-all block">
        <p class="erp-kpi-value text-orange-700">{{ number_format($finance_stats['active_loans']) }}</p>
        <p class="erp-kpi-label text-orange-700">Active Loans</p>
    </a>
    <div class="erp-kpi border-red-200 bg-red-50/60">
        <p class="erp-kpi-value text-red-700 tabular-nums">৳{{ number_format($finance_stats['active_loan_balance'], 0) }}</p>
        <p class="erp-kpi-label text-red-700">Outstanding Balance</p>
    </div>
    <a href="{{ route('admin.hrm.finance.final-settlement.index') }}" class="erp-kpi border-sky-200 bg-sky-50/60 hover:border-brand/40 transition-all block">
        <p class="erp-kpi-value text-sky-700">{{ number_format($finance_stats['pending_final_settlements']) }}</p>
        <p class="erp-kpi-label text-sky-700">F&F Pending</p>
    </a>
    <a href="{{ route('admin.hrm.finance.tax.index') }}" class="erp-kpi border-violet-200 bg-violet-50/60 hover:border-brand/40 transition-all block">
        <p class="erp-kpi-value text-violet-700 tabular-nums">৳{{ number_format($finance_stats['month_tds'], 0) }}</p>
        <p class="erp-kpi-label text-violet-700">TDS This Month</p>
    </a>
    <a href="{{ route('admin.hrm.finance.pf.employer-report') }}" class="erp-kpi border-emerald-200 bg-emerald-50/60 hover:border-brand/40 transition-all block">
        <p class="erp-kpi-value text-emerald-700 tabular-nums">৳{{ number_format($finance_stats['month_pf_employer'], 0) }}</p>
        <p class="erp-kpi-label text-emerald-700">Employer PF (Month)</p>
    </a>
</div>
@endif

@if($performance_stats ?? null)
<h2 class="text-sm font-semibold text-gray-800 mb-3">Performance Overview</h2>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <a href="{{ route('admin.hrm.performance.reviews.index', ['pending_rating' => 1]) }}" class="erp-kpi border-amber-200 bg-amber-50/60 hover:border-brand/40 transition-all block">
        <p class="erp-kpi-value text-amber-700">{{ number_format($performance_stats['pending_rating']) }}</p>
        <p class="erp-kpi-label text-amber-700">Pending Rating</p>
    </a>
    <a href="{{ route('admin.hrm.performance.reviews.index', ['pending_hr' => 1]) }}" class="erp-kpi border-blue-200 bg-blue-50/60 hover:border-brand/40 transition-all block">
        <p class="erp-kpi-value text-blue-700">{{ number_format($performance_stats['pending_hr']) }}</p>
        <p class="erp-kpi-label text-blue-700">Pending HR</p>
    </a>
    <a href="{{ route('admin.hrm.performance.reviews.index', ['status' => 'approved']) }}" class="erp-kpi border-emerald-200 bg-emerald-50/60 hover:border-brand/40 transition-all block">
        <p class="erp-kpi-value text-emerald-700">{{ number_format($performance_stats['approved_month']) }}</p>
        <p class="erp-kpi-label text-emerald-700">Approved This Month</p>
    </a>
    <a href="{{ route('admin.hrm.performance.cycles.index') }}" class="erp-kpi border-violet-200 bg-violet-50/60 hover:border-brand/40 transition-all block">
        <p class="erp-kpi-value text-violet-700">{{ number_format($performance_stats['open_cycles']) }}</p>
        <p class="erp-kpi-label text-violet-700">Open Cycles</p>
    </a>
</div>
@endif

@if(auth()->user()->hasAnyAttendanceViewPermission())
<h2 class="text-sm font-semibold text-gray-800 mb-3">Today's Attendance Data</h2>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    @php
        $todayCards = [
            ['present', 'Total Present', $today_stats['present'], 'border-brand/20 bg-brand/5', 'text-brand'],
            ['male_present', 'Male Present', $today_stats['male_present'], 'border-emerald-200 bg-emerald-50/60', 'text-emerald-700'],
            ['female_present', 'Female Present', $today_stats['female_present'], 'border-orange-200 bg-orange-50/60', 'text-orange-700'],
            ['absent', 'Total Absent', $today_stats['absent'], 'border-red-200 bg-red-50/60', 'text-red-700'],
        ];
        $detailBase = array_filter([
            'date' => $filters['date'],
            'factory_id' => $filters['factory_id'] ?? null,
        ]);
    @endphp
    @foreach($todayCards as [$type, $label, $value, $panel, $text])
        <a href="{{ route('admin.hrm.dashboard.today-attendance', array_merge($detailBase, ['type' => $type])) }}"
           class="erp-kpi {{ $panel }} hover:border-brand/40 hover:shadow-sm transition-all block">
            <p class="erp-kpi-value {{ $text }}">{{ number_format($value) }}</p>
            <p class="erp-kpi-label {{ $text }}">{{ $label }}</p>
            <p class="text-[10px] text-gray-400 mt-1">Click for details →</p>
        </a>
    @endforeach
</div>

<div class="erp-panel overflow-hidden" x-data="{ tab: 'attendance' }">
    <div class="erp-panel-head flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Today's Details</h2>
        <div class="flex gap-1">
            @foreach(['attendance' => 'Attendance', 'leave' => 'Leave', 'shift' => 'Shift'] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                        class="px-2.5 py-1 text-[11px] font-semibold rounded-sm transition-colors"
                        :class="tab === '{{ $key }}' ? 'bg-brand text-white' : 'text-gray-500 hover:bg-gray-100'">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div x-show="tab === 'attendance'" x-cloak class="overflow-x-auto">
        <div class="px-4 py-2 border-b border-erp-border bg-gray-50/50">
            <p class="text-[11px] font-medium text-gray-600">Today's Attendance Data (Department Wise)</p>
        </div>
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th class="text-right">Total Present</th>
                    <th class="text-right">Male</th>
                    <th class="text-right">Female</th>
                    <th class="text-right">Total Absent</th>
                </tr>
            </thead>
            <tbody>
                @forelse($today_departments as $row)
                    <tr>
                        <td class="font-medium text-sm">{{ $row->department_name }}</td>
                        <td class="text-right tabular-nums text-sm text-emerald-700">{{ number_format($row->present_count) }}</td>
                        <td class="text-right tabular-nums text-sm">{{ number_format($row->male_count) }}</td>
                        <td class="text-right tabular-nums text-sm">{{ number_format($row->female_count) }}</td>
                        <td class="text-right tabular-nums text-sm text-red-600">{{ number_format($row->absent_count) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-8 text-gray-400">No attendance records for this date.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div x-show="tab === 'leave'" x-cloak class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th class="text-right">On Leave</th>
                </tr>
            </thead>
            <tbody>
                @forelse($today_leave as $row)
                    <tr>
                        <td class="font-medium text-sm">{{ $row->department_name }}</td>
                        <td class="text-right tabular-nums text-sm text-purple-700">{{ number_format($row->leave_count) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center py-8 text-gray-400">No leave records for this date.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div x-show="tab === 'shift'" x-cloak class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Shift</th>
                    <th class="text-right">Present</th>
                    <th class="text-right">Absent</th>
                    <th class="text-right">Total Logs</th>
                </tr>
            </thead>
            <tbody>
                @forelse($today_shifts as $row)
                    <tr>
                        <td class="font-medium text-sm">{{ $row->shift_name }}</td>
                        <td class="text-right tabular-nums text-sm text-emerald-700">{{ number_format($row->present_count) }}</td>
                        <td class="text-right tabular-nums text-sm text-red-600">{{ number_format($row->absent_count) }}</td>
                        <td class="text-right tabular-nums text-sm">{{ number_format($row->total_count) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-8 text-gray-400">No shift data for this date.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
