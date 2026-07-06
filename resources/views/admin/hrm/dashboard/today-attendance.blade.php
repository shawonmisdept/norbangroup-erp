@extends('layouts.admin')

@section('title', 'Today\'s Attendance — ' . config('app.name'))

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard', array_filter(['factory_id' => $filters['factory_id'] ?? null, 'date' => $filters['date']])) }}" class="hover:text-brand">Dashboard</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Today's Attendance</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $typeLabels[$type] ?? 'Today\'s Attendance',
    'subtitle' => $dateLabel . ' — employee-wise attendance detail',
    'actions' => '<a href="' . route('admin.hrm.dashboard', array_filter(['factory_id' => $filters['factory_id'] ?? null, 'date' => $filters['date']])) . '" class="erp-btn-secondary">← Back to Dashboard</a>',
])

<div class="flex flex-wrap gap-2 mb-4">
    @foreach($typeLabels as $key => $label)
        <a href="{{ route('admin.hrm.dashboard.today-attendance', array_filter(array_merge($filters, ['type' => $key]))) }}"
           class="px-3 py-1.5 text-[11px] font-semibold rounded-sm border transition-colors {{ $type === $key ? 'bg-brand text-white border-brand' : 'bg-white text-gray-600 border-erp-border hover:border-brand/40' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.dashboard.today-attendance') }}" class="space-y-3">
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="date" value="{{ $filters['date'] }}">
            @if($filters['factory_id'] ?? null)
                <input type="hidden" name="factory_id" value="{{ $filters['factory_id'] }}">
            @endif

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @if(count($factories) > 1 && ! auth()->user()->factory_id)
                    <div>
                        <label class="erp-form-label">Company</label>
                        <select name="factory_id" class="erp-input !text-xs">
                            <option value="">All Companies</option>
                            @foreach($factories as $id => $name)
                                <option value="{{ $id }}" {{ (string) ($filters['factory_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="erp-form-label">Official Status</label>
                    <select name="status" class="erp-input !text-xs">
                        <option value="">All</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="erp-form-label">Global Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Employee code or name…" class="erp-input !text-xs">
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="erp-btn-primary">Apply Filter</button>
                <a href="{{ route('admin.hrm.dashboard.today-attendance', array_filter(['type' => $type, 'date' => $filters['date'], 'factory_id' => $filters['factory_id'] ?? null])) }}" class="erp-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head flex items-center justify-between">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Employee List</h2>
        <span class="text-[11px] text-gray-400">{{ $logs->total() }} record(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th class="w-10">Sl.</th>
                    <th>
                        Employee Code
                        <form method="GET" class="mt-1">
                            @foreach(array_filter($filters) as $k => $v)
                                @if(! in_array($k, ['employee_code'], true))
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endif
                            @endforeach
                            <input type="text" name="employee_code" value="{{ $filters['employee_code'] ?? '' }}" placeholder="Search code" class="erp-input !text-[10px] !py-1 !px-1.5 font-normal">
                        </form>
                    </th>
                    <th>
                        Full Name
                        <form method="GET" class="mt-1">
                            @foreach(array_filter($filters) as $k => $v)
                                @if(! in_array($k, ['name'], true))
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endif
                            @endforeach
                            <input type="text" name="name" value="{{ $filters['name'] ?? '' }}" placeholder="Search name" class="erp-input !text-[10px] !py-1 !px-1.5 font-normal">
                        </form>
                    </th>
                    <th>Company</th>
                    <th>
                        Department
                        <form method="GET" class="mt-1">
                            @foreach(array_filter($filters) as $k => $v)
                                @if(! in_array($k, ['department'], true))
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endif
                            @endforeach
                            <input type="text" name="department" value="{{ $filters['department'] ?? '' }}" placeholder="Search dept" class="erp-input !text-[10px] !py-1 !px-1.5 font-normal">
                        </form>
                    </th>
                    <th>
                        Designation
                        <form method="GET" class="mt-1">
                            @foreach(array_filter($filters) as $k => $v)
                                @if(! in_array($k, ['designation'], true))
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endif
                            @endforeach
                            <input type="text" name="designation" value="{{ $filters['designation'] ?? '' }}" placeholder="Search desig." class="erp-input !text-[10px] !py-1 !px-1.5 font-normal">
                        </form>
                    </th>
                    <th>
                        Line
                        <form method="GET" class="mt-1">
                            @foreach(array_filter($filters) as $k => $v)
                                @if(! in_array($k, ['line'], true))
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endif
                            @endforeach
                            <input type="text" name="line" value="{{ $filters['line'] ?? '' }}" placeholder="Search line" class="erp-input !text-[10px] !py-1 !px-1.5 font-normal">
                        </form>
                    </th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Late</th>
                    <th>Early</th>
                    <th>Official Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $index => $log)
                    @php
                        $employee = $log->employee;
                        $badge = match(true) {
                            $log->status === 'present' => 'bg-green-100 text-green-800',
                            $log->status === 'late' => 'bg-amber-100 text-amber-800',
                            $log->status === 'absent' => 'bg-red-100 text-red-800',
                            $log->status === 'half_day' => 'bg-orange-100 text-orange-800',
                            $log->status === 'leave' => 'bg-purple-100 text-purple-800',
                            $log->status === 'off_day' => 'bg-gray-100 text-gray-600',
                            $log->status === 'holiday' => 'bg-blue-100 text-blue-800',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <tr>
                        <td class="text-xs text-gray-500 tabular-nums">{{ $logs->firstItem() + $index }}</td>
                        <td class="font-mono text-xs">{{ $employee?->employee_code ?? '—' }}</td>
                        <td class="font-medium text-sm">{{ $employee?->name ?? '—' }}</td>
                        <td class="text-xs">{{ $employee?->factory?->name ?? '—' }}</td>
                        <td class="text-xs">{{ $employee?->department?->name ?? '—' }}</td>
                        <td class="text-xs">{{ $employee?->designation?->name ?? '—' }}</td>
                        <td class="text-xs">{{ $employee?->line?->name ?? '—' }}</td>
                        <td class="text-xs tabular-nums">@portalTime($log->check_in)</td>
                        <td class="text-xs tabular-nums">@portalTime($log->check_out)</td>
                        <td class="text-xs tabular-nums">{{ $log->late_minutes > 0 ? $log->late_minutes . 'm' : '—' }}</td>
                        <td class="text-xs tabular-nums">{{ $log->early_leave_minutes > 0 ? $log->early_leave_minutes . 'm' : '—' }}</td>
                        <td><span class="erp-badge {{ $badge }}">{{ $log->displayStatusLabel() }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center py-10 text-gray-400">No data found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="px-4 py-3 border-t border-erp-border bg-gray-50/50">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
