@extends('layouts.admin')

@section('title', 'Attendance Calendar — ' . $employee->employee_code)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.attendance.reports.index') }}" class="hover:text-brand">Reports</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $employee->employee_code }}</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'reports'])

@include('partials.erp.page-header', [
    'title' => 'Employee Calendar',
    'subtitle' => $employee->name . ' · ' . $periodLabel,
    'actions' => '<a href="' . route('admin.hrm.attendance.reports.index', ['factory_id' => $employee->factory_id, 'year' => $year, 'month' => $month]) . '" class="erp-btn-secondary">← Reports</a>',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="w-28">
                <label class="erp-form-label">Year</label>
                <input type="number" name="year" value="{{ $year }}" class="erp-input !text-xs">
            </div>
            <div class="w-36">
                <label class="erp-form-label">Month</label>
                <select name="month" class="erp-input !text-xs">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m)->format('F') }}</option>
                    @endfor
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary">Go</button>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">{{ $periodLabel }}</h2>
        <span class="text-[11px] text-gray-400">{{ $employee->department?->name ?? '—' }} · {{ $employee->line?->name ?? '—' }}</span>
    </div>
    <div class="erp-panel-body">
        <div class="grid grid-cols-7 gap-1.5 text-center text-[10px] font-semibold uppercase text-gray-400 mb-2">
            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
                <div>{{ $d }}</div>
            @endforeach
        </div>
        @php
            $firstDow = \Carbon\Carbon::create($year, $month, 1)->dayOfWeek;
            $cells = array_merge(array_fill(0, $firstDow, null), $days);
            $rows = array_chunk($cells, 7);
            if (count(end($rows)) < 7) {
                $rows[count($rows) - 1] = array_merge(end($rows), array_fill(0, 7 - count(end($rows)), null));
            }
        @endphp
        @foreach($rows as $week)
            <div class="grid grid-cols-7 gap-1.5 mb-1.5">
                @foreach($week as $cell)
                    @if($cell === null)
                        <div class="aspect-square rounded-sm bg-transparent"></div>
                    @else
                        @php
                            $log = $cell['log'];
                            $badge = match($log?->status) {
                                'present' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'late' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'absent' => 'bg-red-100 text-red-800 border-red-200',
                                'half_day' => 'bg-orange-100 text-orange-800 border-orange-200',
                                'leave' => 'bg-violet-100 text-violet-800 border-violet-200',
                                'off_day', 'holiday' => 'bg-gray-100 text-gray-500 border-gray-200',
                                default => 'bg-white text-gray-400 border-erp-border/60',
                            };
                            $label = $log
                                ? ($log->status === 'half_day' ? $log->displayStatusLabel() : $log->lateStatusLabel())
                                : '—';
                        @endphp
                        <div class="aspect-square rounded-sm border p-1 flex flex-col {{ $badge }}">
                            <span class="text-[11px] font-bold tabular-nums">{{ $cell['date']->format('j') }}</span>
                            <span class="mt-auto text-[8px] leading-tight line-clamp-2">{{ $label }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
</div>
@endsection
