@extends('layouts.admin')

@section('title', 'Half Day Entry')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Half Day Entry</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'half-day-entry'])

@include('partials.erp.page-header', [
    'title' => 'Half Day Entries',
    'subtitle' => 'HR manual first/second half day records — overrides auto detection',
    'actions' => auth()->user()?->canManageAttendanceSubmodule('half-day-entry')
        ? '<a href="' . route('admin.hrm.attendance.half-day-entry.create') . '" class="erp-btn-primary">+ New Entry</a>'
        : '',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[180px]">
                <label class="erp-form-label">Search</label>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or code…" class="erp-input !text-xs">
            </div>
            @if(count($factories) > 1)
                <div class="w-44">
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" class="erp-input !text-xs">
                        <option value="">All units</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ (string) ($filters['factory_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button type="submit" class="erp-btn-secondary">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Half</th>
                    <th>Pay Ratio</th>
                    <th>Source</th>
                    <th>Notes</th>
                    @if(auth()->user()?->canManageAttendanceSubmodule('half-day-entry'))
                        <th></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td class="text-xs tabular-nums">{{ $entry->attendance_date->format('d M Y') }}</td>
                        <td>
                            <p class="font-medium text-sm">{{ $entry->employee?->name }}</p>
                            <code class="text-[10px] text-gray-400">{{ $entry->employee?->employee_code }}</code>
                        </td>
                        <td class="text-sm">{{ $entry->halfDayTypeLabel() }}</td>
                        <td class="text-xs tabular-nums">{{ $entry->half_day_pay_ratio !== null ? number_format((float) $entry->half_day_pay_ratio, 2) : 'Default' }}</td>
                        <td>
                            @if($entry->is_manual_half_day)
                                <span class="erp-badge bg-blue-100 text-blue-800">Manual</span>
                            @else
                                <span class="erp-badge bg-gray-100 text-gray-600">Auto</span>
                            @endif
                        </td>
                        <td class="text-xs text-gray-500 max-w-xs truncate">{{ $entry->half_day_notes ?? '—' }}</td>
                        @if(auth()->user()?->canManageAttendanceSubmodule('half-day-entry'))
                            <td class="text-right">
                                @if($entry->is_manual_half_day)
                                    <form method="POST" action="{{ route('admin.hrm.attendance.half-day-entry.destroy', $entry) }}" data-confirm="Remove this half day entry?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="erp-btn-sm-secondary !text-red-600">Remove</button>
                                    </form>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ auth()->user()?->canManageAttendanceSubmodule('half-day-entry') ? 7 : 6 }}" class="text-center py-10 text-gray-400">No half day records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())
        <div class="px-4 py-3 border-t border-erp-border">{{ $entries->links() }}</div>
    @endif
</div>
@endsection
