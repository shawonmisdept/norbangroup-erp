@extends('layouts.admin')

@section('title', 'Manual Punch')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Manual Punch</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'manual-punch'])

@include('partials.erp.page-header', [
    'title' => 'Manual Punch Entries',
    'subtitle' => 'Fix missed IN/OUT punches — updates attendance instantly',
    'actions' => auth()->user()?->canManageAttendanceSubmodule('manual-punch')
        ? '<a href="' . route('admin.hrm.attendance.manual-punch.create') . '" class="erp-btn-primary">+ Add Punch</a>'
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
            <button type="submit" class="erp-btn-primary">Filter</button>
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
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Reason</th>
                    <th>Entered By</th>
                    @if(auth()->user()?->canManageAttendanceSubmodule('manual-punch'))
                        <th class="text-right">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $row)
                    <tr>
                        <td class="text-xs tabular-nums whitespace-nowrap">{{ $row->date->format('d M Y') }}</td>
                        <td>
                            <p class="font-medium text-xs">{{ $row->employee?->name ?? '—' }}</p>
                            <p class="text-[10px] text-gray-400 font-mono">{{ $row->employee?->employee_code }}</p>
                        </td>
                        <td class="text-xs tabular-nums whitespace-nowrap">
                            @if($row->in)
                                <span class="erp-badge bg-emerald-100 text-emerald-800 mr-1">IN</span>
                                @portalTime($row->in->punched_at)
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="text-xs tabular-nums whitespace-nowrap">
                            @if($row->out)
                                <span class="erp-badge bg-amber-100 text-amber-800 mr-1">OUT</span>
                                @portalTime($row->out->punched_at)
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="text-xs text-gray-600 max-w-xs">
                            @if($row->in)
                                <p class="truncate" title="{{ $row->in->reason }}"><span class="text-[10px] text-gray-400">IN:</span> {{ $row->in->reason }}</p>
                            @endif
                            @if($row->out)
                                <p class="truncate {{ $row->in ? 'mt-0.5' : '' }}" title="{{ $row->out->reason }}"><span class="text-[10px] text-gray-400">OUT:</span> {{ $row->out->reason }}</p>
                            @endif
                            @if(! $row->in && ! $row->out)
                                —
                            @endif
                        </td>
                        <td class="text-xs max-w-[160px]">
                            @if($row->in)
                                <p class="font-medium">{{ $row->in->enteredByUser?->name ?? '—' }}</p>
                                <p class="text-[10px] text-gray-400 tabular-nums">@portalDateTime($row->in->created_at)</p>
                            @endif
                            @if($row->out)
                                <p class="font-medium {{ $row->in ? 'mt-1' : '' }}">{{ $row->out->enteredByUser?->name ?? '—' }}</p>
                                <p class="text-[10px] text-gray-400 tabular-nums">@portalDateTime($row->out->created_at)</p>
                            @endif
                        </td>
                        @if(auth()->user()?->canManageAttendanceSubmodule('manual-punch'))
                            <td class="text-right align-top">
                                <div class="flex flex-col items-end gap-1">
                                    @if($row->in)
                                        <div class="flex items-center gap-1">
                                            <a href="{{ route('admin.hrm.attendance.manual-punch.edit', $row->in) }}" class="erp-btn-sm-secondary !py-0.5 !px-2 text-[10px]">Edit IN</a>
                                            <form method="POST" action="{{ route('admin.hrm.attendance.manual-punch.destroy', $row->in) }}" data-confirm="Remove this check-in punch?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="erp-btn-danger !py-0.5 !px-2 text-[10px]">Remove</button>
                                            </form>
                                        </div>
                                    @endif
                                    @if($row->out)
                                        <div class="flex items-center gap-1">
                                            <a href="{{ route('admin.hrm.attendance.manual-punch.edit', $row->out) }}" class="erp-btn-sm-secondary !py-0.5 !px-2 text-[10px]">Edit OUT</a>
                                            <form method="POST" action="{{ route('admin.hrm.attendance.manual-punch.destroy', $row->out) }}" data-confirm="Remove this check-out punch?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="erp-btn-danger !py-0.5 !px-2 text-[10px]">Remove</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ auth()->user()?->canManageAttendanceSubmodule('manual-punch') ? 7 : 6 }}" class="text-center text-sm text-gray-400 py-10">No manual punches yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())
        <div class="erp-panel-footer">{{ $entries->links() }}</div>
    @endif
</div>
@endsection
