@extends('layouts.admin')

@section('title', 'Attendance Periods — ' . config('app.name'))

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Periods</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'periods'])

@include('partials.erp.page-header', [
    'title' => 'Attendance Periods',
    'subtitle' => 'Draft → Process → Freeze cycle for payroll input',
])

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-4">
    @if(auth()->user()->canManageAttendanceSubmodule('periods'))
        <div class="erp-panel xl:col-span-2">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Process Period</h2>
            </div>
            <div class="erp-panel-body">
                <form method="POST" action="{{ route('admin.hrm.attendance.process') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    @csrf
                    <div>
                        <label class="erp-form-label">Factory</label>
                        <select name="factory_id" required class="erp-input !text-xs">
                            @foreach($factories as $id => $name)
                                <option value="{{ $id }}" {{ count($factories) === 1 ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="erp-form-label">Year</label>
                        <input type="number" name="year" value="{{ now()->year }}" required class="erp-input !text-xs">
                    </div>
                    <div>
                        <label class="erp-form-label">Month</label>
                        <select name="month" required class="erp-input !text-xs">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ now()->month === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m)->format('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 text-xs text-gray-600">
                            <input type="hidden" name="mark_absences" value="0">
                            <input type="checkbox" name="mark_absences" value="1" checked class="rounded border-gray-300 text-brand">
                            Mark absences
                        </label>
                        <button type="submit" class="erp-btn-primary !py-2 !px-4 text-xs">Process</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="erp-panel">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Process Today</h2>
            </div>
            <div class="erp-panel-body space-y-3">
                <p class="text-xs text-gray-500">Quick process for today's unprocessed punches only.</p>
                <form method="POST" action="{{ route('admin.hrm.attendance.process-today') }}">
                    @csrf
                    @if(count($factories) > 1)
                        <select name="factory_id" class="erp-input !text-xs mb-2">
                            <option value="">All units</option>
                            @foreach($factories as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="factory_id" value="{{ array_key_first($factories) }}">
                    @endif
                    <button type="submit" class="erp-btn-secondary w-full justify-center !py-2 text-xs">Process Today</button>
                </form>
            </div>
        </div>
    @endif
</div>

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.attendance.periods') }}" class="flex flex-wrap items-end gap-3">
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
            <div class="w-36">
                <label class="erp-form-label">Status</label>
                <select name="status" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Periods</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Factory</th>
                    <th>Date Range</th>
                    <th>Status</th>
                    <th>Processed</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($periods as $period)
                    @php
                        $badge = match($period->status) {
                            'draft' => 'bg-gray-100 text-gray-600',
                            'processed' => 'bg-blue-100 text-blue-800',
                            'frozen' => 'bg-green-100 text-green-800',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <tr>
                        <td class="font-medium">{{ $period->periodLabel() }}</td>
                        <td class="text-xs">{{ $period->factory?->name }}</td>
                        <td class="text-xs tabular-nums">{{ $period->start_date->format('d M') }} – {{ $period->end_date->format('d M Y') }}</td>
                        <td><span class="erp-badge {{ $badge }}">{{ $period->statusLabel() }}</span></td>
                        <td class="text-xs text-gray-500">
                            @portalDateTime($period->processed_at)
                            @if($period->processedByUser)
                                <br><span class="text-gray-400">{{ $period->processedByUser->name }}</span>
                            @endif
                        </td>
                        <td class="text-right space-x-2">
                            @include('partials.erp.table-actions', [
                                'viewUrl' => route('admin.hrm.attendance.periods.show', $period),
                            ])
                            @if(auth()->user()->canManageAttendanceSubmodule('periods') && ! $period->isFrozen())
                                <form method="POST" action="{{ route('admin.hrm.attendance.periods.freeze', $period) }}" class="inline" data-confirm="Freeze {{ $period->periodLabel() }}? This cannot be undone.">
                                    @csrf
                                    <button type="submit" class="erp-btn-primary !py-1 !px-2 text-xs">Freeze</button>
                                </form>
                            @elseif($period->isFrozen())
                                <span class="text-[11px] text-green-700">Locked</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-10 text-gray-400">No periods yet. Run Process to create one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($periods->hasPages())
        <div class="px-4 py-3 border-t border-erp-border bg-gray-50/50">{{ $periods->links() }}</div>
    @endif
</div>
@endsection
