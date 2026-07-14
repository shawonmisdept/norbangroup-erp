@extends('layouts.admin')

@section('title', 'Punch Logs — ' . config('app.name'))

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Punch Logs</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Raw Punch Logs',
    'subtitle' => 'Imported IN/OUT records from ZKTeco ADMS before daily processing',
    'actions' => '<a href="' . route('admin.hrm.attendance.hub') . '" class="erp-btn-secondary">← Attendance Hub</a>',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.attendance.punches') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[180px]">
                <label class="erp-form-label">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Employee, code, biometric ID…" class="erp-input !text-xs">
            </div>
            @if(count($factories) > 1)
                <div class="w-40">
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" class="erp-input !text-xs">
                        <option value="">All</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ (string) ($filters['factory_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="w-40">
                <label class="erp-form-label">Device</label>
                <select name="device_id" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($devices as $id => $name)
                        <option value="{{ $id }}" {{ (string) ($filters['device_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-32">
                <label class="erp-form-label">Mapped</label>
                <select name="mapped" class="erp-input !text-xs">
                    <option value="">All</option>
                    <option value="yes" {{ ($filters['mapped'] ?? '') === 'yes' ? 'selected' : '' }}>Mapped</option>
                    <option value="no" {{ ($filters['mapped'] ?? '') === 'no' ? 'selected' : '' }}>Unmapped</option>
                </select>
            </div>
            <div class="w-36">
                <label class="erp-form-label">From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="erp-input !text-xs">
            </div>
            <div class="w-36">
                <label class="erp-form-label">To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="erp-input !text-xs">
            </div>
            <button type="submit" class="erp-btn-secondary">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Punches</h2>
        <span class="text-[11px] text-gray-400">{{ $punches->total() }} record(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Image</th>
                    <th>Employee</th>
                    <th>Biometric ID</th>
                    <th>Type</th>
                    <th>Device</th>
                    <th>Source</th>
                </tr>
            </thead>
            <tbody>
                @forelse($punches as $punch)
                    <tr>
                        <td class="text-xs tabular-nums whitespace-nowrap">@portalDateTimeSeconds($punch->punched_at)</td>
                        <td class="w-14">
                            @if($punch->photoUrl())
                                <a href="{{ $punch->photoUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-block">
                                    <img
                                        src="{{ $punch->photoUrl() }}"
                                        alt="Punch selfie{{ $punch->employee ? ' for ' . $punch->employee->name : '' }}"
                                        class="w-10 h-10 rounded-sm object-cover border border-gray-200 bg-gray-50"
                                    >
                                </a>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="text-sm">
                            @if($punch->employee)
                                <a href="{{ route('admin.hrm.employees.show', $punch->employee) }}" class="text-brand hover:underline">{{ $punch->employee->name }}</a>
                                <p class="text-[11px] text-gray-400 font-mono">{{ $punch->employee->employee_code }}</p>
                            @else
                                <span class="text-amber-700 text-xs font-medium">Unmapped</span>
                            @endif
                        </td>
                        <td class="font-mono text-xs">{{ $punch->biometric_user_id }}</td>
                        <td><span class="erp-badge bg-gray-100 text-gray-700">{{ $punch->punchTypeLabel() }}</span></td>
                        <td class="text-xs text-gray-500">{{ $punch->biometricDevice?->name ?? '—' }}</td>
                        <td class="text-xs text-gray-500">{{ $punch->sourceLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-10 text-gray-400">No punch records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($punches->hasPages())
        <div class="px-4 py-3 border-t border-erp-border bg-gray-50/50">{{ $punches->links() }}</div>
    @endif
</div>
@endsection
