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
            <button type="submit" class="erp-btn-secondary">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>Entered By</th>
                    @if(auth()->user()?->canManageAttendanceSubmodule('manual-punch'))
                        <th></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td class="text-xs tabular-nums">{{ $entry->punched_at->format('d M Y H:i') }}</td>
                        <td>
                            <p class="font-medium text-xs">{{ $entry->employee?->name ?? '—' }}</p>
                            <p class="text-[10px] text-gray-400 font-mono">{{ $entry->employee?->employee_code }}</p>
                        </td>
                        <td><span class="erp-badge bg-blue-100 text-blue-800">{{ strtoupper($entry->punch_type) }}</span></td>
                        <td class="text-xs text-gray-600 max-w-xs truncate">{{ $entry->reason }}</td>
                        <td class="text-xs">{{ $entry->enteredByUser?->name ?? '—' }}</td>
                        @if(auth()->user()?->canManageAttendanceSubmodule('manual-punch'))
                            <td class="text-right">
                                <form method="POST" action="{{ route('admin.hrm.attendance.manual-punch.destroy', $entry) }}" data-confirm="Remove this manual punch?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="erp-btn-sm-secondary !text-red-600">Remove</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ auth()->user()?->canManageAttendanceSubmodule('manual-punch') ? 6 : 5 }}" class="text-center text-sm text-gray-400 py-10">No manual punches yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())
        <div class="erp-panel-footer">{{ $entries->links() }}</div>
    @endif
</div>
@endsection
