@extends('layouts.admin')

@section('title', 'Attendance Reports')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Reports</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'reports'])

@include('partials.erp.page-header', [
    'title' => 'Attendance Reports',
    'subtitle' => 'Monthly summary, department & line breakdown, late analysis',
    'actions' => $factoryId
        ? '<a href="' . route('admin.hrm.attendance.reports.export', $filters) . '" class="erp-btn-secondary">Export CSV</a>'
        : '',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            @if(count($factories) > 1)
                <div class="w-44">
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" class="erp-input !text-xs" required>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ (string) $factoryId === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="factory_id" value="{{ $factoryId }}">
            @endif
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
            <button type="submit" class="erp-btn-secondary">Run Report</button>
        </form>
    </div>
</div>

@if($factoryId)
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-3 mb-4">
        @foreach([
            ['label' => 'Employees', 'value' => $summary['employees'] ?? 0, 'class' => 'text-brand'],
            ['label' => 'Present', 'value' => $summary['present'] ?? 0, 'class' => 'text-emerald-700'],
            ['label' => 'Late', 'value' => $summary['late'] ?? 0, 'class' => 'text-amber-700'],
            ['label' => 'Absent', 'value' => $summary['absent'] ?? 0, 'class' => 'text-red-700'],
            ['label' => 'Half Day', 'value' => $summary['half_day'] ?? 0, 'class' => 'text-orange-700'],
            ['label' => 'Leave', 'value' => $summary['leave'] ?? 0, 'class' => 'text-violet-700'],
            ['label' => 'Off Day', 'value' => $summary['off_day'] ?? 0, 'class' => 'text-gray-600'],
        ] as $stat)
            <div class="erp-kpi text-center">
                <p class="erp-kpi-value {{ $stat['class'] }}">{{ $stat['value'] }}</p>
                <p class="erp-kpi-label">{{ $stat['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-4">
        <div class="erp-panel overflow-hidden">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">By Department — {{ $periodLabel }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Emp</th>
                            <th>Present</th>
                            <th>Late</th>
                            <th>Absent</th>
                            <th>Half</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byDepartment as $row)
                            <tr>
                                <td class="font-medium text-sm">{{ $row->department_name }}</td>
                                <td class="tabular-nums">{{ $row->employee_count }}</td>
                                <td class="tabular-nums">{{ $row->present_count }}</td>
                                <td class="tabular-nums">{{ $row->late_count }}</td>
                                <td class="tabular-nums">{{ $row->absent_count }}</td>
                                <td class="tabular-nums">{{ $row->half_day_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-8 text-gray-400">No data for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="erp-panel overflow-hidden">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">By Line — {{ $periodLabel }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Line</th>
                            <th>Emp</th>
                            <th>Present</th>
                            <th>Late</th>
                            <th>Absent</th>
                            <th>Half</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byLine as $row)
                            <tr>
                                <td class="font-medium text-sm">{{ $row->line_name }}</td>
                                <td class="tabular-nums">{{ $row->employee_count }}</td>
                                <td class="tabular-nums">{{ $row->present_count }}</td>
                                <td class="tabular-nums">{{ $row->late_count }}</td>
                                <td class="tabular-nums">{{ $row->absent_count }}</td>
                                <td class="tabular-nums">{{ $row->half_day_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-8 text-gray-400">No data for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="erp-panel overflow-hidden">
        <div class="erp-panel-head">
            <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Top Late Employees</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Late Days</th>
                        <th>Forgiven</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topLate as $row)
                        <tr>
                            <td>
                                <p class="font-medium text-sm">{{ $row->name }}</p>
                                <code class="text-[10px] text-gray-400">{{ $row->employee_code }}</code>
                            </td>
                            <td class="tabular-nums font-semibold">{{ $row->late_count }}</td>
                            <td class="tabular-nums text-green-700">{{ $row->forgiven_count }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.hrm.attendance.reports.employee', ['employee' => $row->employee_id, 'year' => $year, 'month' => $month]) }}"
                                   class="erp-btn-sm-secondary">Calendar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-8 text-gray-400">No late records this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="erp-panel">
        <div class="erp-panel-body text-center py-10 text-gray-400">Select a factory to run the report.</div>
    </div>
@endif
@endsection
