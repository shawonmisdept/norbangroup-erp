@extends('layouts.admin')
@section('title', 'Daily KM Log')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Daily KM Log',
    'subtitle' => 'Record morning and evening KM separately — authority may edit corrections',
    'actions' => auth()->user()->canManageTmsSubmodule('odometer')
        ? '<a href="' . route('admin.tms.odometer.morning.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Record Morning KM</a>'
        : '',
])

<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
    @if($factories !== [])
        <div>
            <label class="erp-label">Unit</label>
            <select name="factory_id" class="erp-input">
                <option value="">All</option>
                @foreach($factories as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['factory_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div>
        <label class="erp-label">From</label>
        <input type="date" name="from" class="erp-input" value="{{ $filters['from'] ?? '' }}">
    </div>
    <div>
        <label class="erp-label">To</label>
        <input type="date" name="to" class="erp-input" value="{{ $filters['to'] ?? '' }}">
    </div>
    <div>
        <label class="erp-label">Vehicle</label>
        <select name="vehicle_id" class="erp-input">
            <option value="">All</option>
            @foreach($vehicles as $v)
                <option value="{{ $v->id }}" @selected(($filters['vehicle_id'] ?? '') == $v->id)>{{ $v->displayLabel() }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="erp-btn-primary">Apply</button>
        <a href="{{ route('admin.tms.odometer.index') }}" class="erp-btn-secondary">Reset</a>
    </div>
</form>

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Vehicle</th>
                <th>Driver</th>
                <th>Morning KM</th>
                <th>Evening KM</th>
                <th>Daily KM</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="tabular-nums text-xs">{{ $log->log_date?->format('d M Y') }}</td>
                    <td class="text-xs">{{ $log->vehicle?->displayLabel() }}</td>
                    <td class="text-xs">{{ $log->vehicle?->assignedDriverNames() ?? '—' }}</td>
                    <td class="tabular-nums">
                        @if($log->hasMorning())
                            {{ number_format($log->morning_km, 2) }}
                            @if($log->morningRecordedTime())
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $log->morningRecordedTime() }}</p>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="tabular-nums">
                        @if($log->hasEvening())
                            {{ number_format($log->evening_km, 2) }}
                            @if($log->eveningRecordedTime())
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $log->eveningRecordedTime() }}</p>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="tabular-nums font-medium">{{ $log->dailyKm() !== null ? number_format($log->dailyKm(), 2) : '—' }}</td>
                    <td><span class="erp-badge {{ $log->statusBadgeClass() }}">{{ $log->statusLabel() }}</span></td>
                    <td class="text-right">
                        <div class="inline-flex gap-1">
                            @if(auth()->user()->canManageTmsSubmodule('odometer') && $log->needsEvening())
                                <a href="{{ route('admin.tms.odometer.evening.create', $log) }}" class="erp-btn-sm-primary">Evening KM</a>
                            @endif
                            @if(auth()->user()->canManageTmsSubmodule('odometer'))
                                <a href="{{ route('admin.tms.odometer.edit', $log) }}" class="erp-btn-sm-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.tms.odometer.destroy', $log) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="erp-btn-sm-secondary" data-confirm="Delete this daily KM log?">Delete</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center py-8 text-gray-400">No odometer logs yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($logs->hasPages())
        <div class="px-4 py-3 border-t">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
