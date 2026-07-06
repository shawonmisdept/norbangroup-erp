@extends('layouts.admin')

@section('title', 'Sync Failures')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.attendance.sync.index') }}" class="hover:text-brand">Sync</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Failures</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Biometric Sync Failures',
    'subtitle' => 'Failed pulls, stale push devices, and error history',
    'actions' => '<a href="' . route('admin.hrm.attendance.sync.index') . '" class="erp-btn-secondary">← Sync Dashboard</a>',
])

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="erp-panel border-red-200 bg-red-50/40">
        <div class="erp-panel-body text-center">
            <p class="text-2xl font-bold text-red-700">{{ $stats['failed'] }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Failed Sync</p>
        </div>
    </div>
    <div class="erp-panel border-amber-200 bg-amber-50/40">
        <div class="erp-panel-body text-center">
            <p class="text-2xl font-bold text-amber-700">{{ $stats['stale'] }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Stale (>{{ $staleMinutes }}m)</p>
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-body text-center">
            <p class="text-2xl font-bold text-brand">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Active Devices</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-4">
    <div class="erp-panel overflow-hidden">
        <div class="erp-panel-head">
            <h2 class="text-xs font-semibold text-red-700 uppercase tracking-wide">Failed Devices</h2>
        </div>
        <div class="divide-y divide-erp-border">
            @forelse($failedDevices as $device)
                <div class="px-4 py-3 text-xs">
                    <p class="font-semibold text-gray-900">{{ $device->name }} <span class="text-gray-400 font-normal">· {{ $device->factory?->name }}</span></p>
                    <p class="text-red-600 mt-1">{{ $device->last_sync_message ?? 'Sync failed' }}</p>
                    @if($device->last_synced_at)
                        <p class="text-gray-400 mt-0.5 tabular-nums">Last attempt @portalDateTime($device->last_synced_at)</p>
                    @endif
                    @if($device->hasAdmsEndpoint())
                        <form method="POST" action="{{ route('admin.hrm.attendance.devices.sync', $device) }}" class="mt-2">
                            @csrf
                            <button type="submit" class="erp-btn-secondary !py-1 !px-2 text-xs">Retry Pull Sync</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="px-4 py-8 text-center text-gray-400">No devices with failed sync status.</p>
            @endforelse
        </div>
    </div>

    <div class="erp-panel overflow-hidden">
        <div class="erp-panel-head">
            <h2 class="text-xs font-semibold text-amber-700 uppercase tracking-wide">Stale / Offline Devices</h2>
        </div>
        <div class="divide-y divide-erp-border">
            @forelse($staleDevices as $device)
                <div class="px-4 py-3 text-xs">
                    <p class="font-semibold text-gray-900">{{ $device->name }} <span class="text-gray-400 font-normal">· {{ $device->factory?->name }}</span></p>
                    <p class="text-amber-700 mt-1">
                        @if($device->last_seen_at)
                            Last push {{ $device->last_seen_at->diffForHumans() }}
                        @elseif($device->last_synced_at)
                            Last pull {{ $device->last_synced_at->diffForHumans() }}
                        @else
                            Never connected
                        @endif
                    </p>
                    <p class="text-gray-400 mt-0.5 font-mono">{{ $device->device_serial ?: 'No serial' }}</p>
                </div>
            @empty
                <p class="px-4 py-8 text-center text-gray-400">All devices reported within {{ $staleMinutes }} minutes.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Failed Sync Runs</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Device</th>
                    <th>Factory</th>
                    <th>Message</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                @forelse($failedLogs as $log)
                    <tr>
                        <td class="font-medium">{{ $log->biometricDevice?->name ?? '—' }}</td>
                        <td class="text-xs">{{ $log->biometricDevice?->factory?->name ?? '—' }}</td>
                        <td class="text-xs text-red-700">{{ $log->message }}</td>
                        <td class="text-xs tabular-nums text-gray-500">@portalDateTime($log->started_at)</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-8 text-gray-400">No failed sync runs logged.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($failedLogs->hasPages())
        <div class="erp-panel-body border-t border-erp-border">{{ $failedLogs->links() }}</div>
    @endif
</div>
@endsection
