@extends('layouts.admin')

@section('title', 'Attendance — ' . config('app.name'))

@section('breadcrumbs')
    <span class="text-gray-600 font-medium">HRM</span>
    <span>/</span>
    <span class="text-gray-800 font-medium">Attendance</span>
@endsection

@section('admin-content')
@php
    $syncActions = '<div class="flex flex-wrap gap-2">'
        . '<a href="' . route('admin.hrm.attendance.daily') . '" class="erp-btn-secondary">Daily Summary</a>'
        . '<a href="' . route('admin.hrm.attendance.periods') . '" class="erp-btn-secondary">Periods</a>'
        . '<a href="' . route('admin.hrm.attendance.punches') . '" class="erp-btn-secondary">Punch Logs</a>';

    if (auth()->user()->hasPermission('hrm.attendance.sync')) {
        $syncActions .= '<form method="POST" action="' . route('admin.hrm.attendance.sync-all') . '" class="inline">'
            . csrf_field()
            . '<button type="submit" class="erp-btn-primary">Sync All Devices</button>'
            . '</form>';
    }

    if (auth()->user()->hasPermission('hrm.attendance.manage')) {
        $syncActions .= '<form method="POST" action="' . route('admin.hrm.attendance.process-today') . '" class="inline">'
            . csrf_field()
            . '<button type="submit" class="erp-btn-secondary">Process Today</button>'
            . '</form>';
    }

    $syncActions .= '</div>';
@endphp

@include('partials.erp.page-header', [
    'title' => 'ZKTeco ADMS Attendance',
    'subtitle' => 'Device sync, punch import, and daily attendance processing',
    'actions' => $syncActions,
])

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
    <div class="erp-panel">
        <div class="erp-panel-body text-center">
            <p class="text-2xl font-bold text-brand">{{ $stats['devices'] }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Active Devices</p>
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-body text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['punches_today'] }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Punches Today</p>
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-body text-center">
            <p class="text-2xl font-bold {{ $stats['unprocessed'] ? 'text-amber-600' : 'text-green-700' }}">{{ $stats['unprocessed'] }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Unprocessed</p>
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-body text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['logs_today'] }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Logs Today</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    <div class="erp-panel overflow-hidden">
        <div class="erp-panel-head">
            <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Biometric Devices</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>Factory</th>
                        <th>Last Sync</th>
                        @if(auth()->user()->hasPermission('hrm.attendance.sync'))
                            <th class="text-right">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($devices as $device)
                        <tr>
                            <td>
                                <p class="font-medium text-sm">{{ $device->name }}</p>
                                <p class="text-[11px] text-gray-400 font-mono">{{ $device->device_serial ?: 'No serial' }}</p>
                            </td>
                            <td class="text-xs">{{ $device->factory?->name }}</td>
                            <td class="text-xs">
                                @if($device->last_synced_at)
                                    <span class="erp-badge {{ $device->last_sync_status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($device->last_sync_status ?? 'unknown') }}
                                    </span>
                                    <p class="text-gray-400 mt-0.5 tabular-nums">{{ $device->last_synced_at->format('d M H:i') }}</p>
                                @else
                                    <span class="text-gray-400">Never</span>
                                @endif
                            </td>
                            @if(auth()->user()->hasPermission('hrm.attendance.sync'))
                                <td class="text-right">
                                    @if($device->hasAdmsEndpoint())
                                        <form method="POST" action="{{ route('admin.hrm.attendance.devices.sync', $device) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="erp-btn-secondary !py-1 !px-2 text-xs">Sync</button>
                                        </form>
                                    @else
                                        <span class="text-[11px] text-amber-600">No ADMS URL</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-8 text-gray-400">No biometric devices configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-4">
        <div class="erp-panel overflow-hidden">
            <div class="erp-panel-head flex items-center justify-between">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Recent Punches</h2>
                <a href="{{ route('admin.hrm.attendance.punches') }}" class="erp-btn-sm-secondary">View all</a>
            </div>
            <div class="divide-y divide-erp-border">
                @forelse($recentPunches as $punch)
                    <div class="px-4 py-3 text-xs flex items-center justify-between gap-3">
                        <div>
                            <p class="font-medium text-gray-900">{{ $punch->employee?->name ?? 'Unmapped' }}</p>
                            <p class="text-gray-400 font-mono">{{ $punch->biometric_user_id }} · {{ $punch->punchTypeLabel() }}</p>
                        </div>
                        <p class="text-gray-500 tabular-nums shrink-0">{{ $punch->punched_at->format('d M H:i') }}</p>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-gray-400">No punches imported yet.</p>
                @endforelse
            </div>
        </div>

        <div class="erp-panel overflow-hidden">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Sync History</h2>
            </div>
            <div class="divide-y divide-erp-border max-h-64 overflow-y-auto">
                @forelse($syncLogs as $log)
                    <div class="px-4 py-3 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-medium">{{ $log->biometricDevice?->name }}</p>
                            <span class="erp-badge {{ $log->status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ ucfirst($log->status) }}</span>
                        </div>
                        <p class="text-gray-500 mt-1">{{ $log->message }}</p>
                        <p class="text-gray-400 mt-0.5 tabular-nums">{{ $log->started_at->format('d M Y H:i') }}</p>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-gray-400">No sync runs yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="erp-panel mt-4">
    <div class="erp-panel-body text-xs text-gray-500 space-y-1">
        <p><strong>Pull sync:</strong> Configure ADMS URL on each device master, then use Sync or schedule <code class="bg-gray-100 px-1 rounded">php artisan hrm:sync-adms</code>.</p>
        <p><strong>Push endpoint:</strong> <code class="bg-gray-100 px-1 rounded">POST {{ url('/api/hrm/adms/push') }}</code> with bearer token <code class="bg-gray-100 px-1 rounded">HRM_ADMS_PUSH_TOKEN</code>.</p>
    </div>
</div>
@endsection
