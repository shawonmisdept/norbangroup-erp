@extends('layouts.admin')
@section('title', 'GPS Tracking')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'GPS Tracking',
    'subtitle' => 'Driver mobile and telematics device positions',
])

@if($settings && ! $settings->gps_tracking_enabled)
    <div class="erp-panel p-4 mb-4 border-l-4 border-amber-400 bg-amber-50/60 text-sm text-amber-900">
        <p class="font-semibold">GPS tracking disabled</p>
        <p class="mt-1 text-amber-800/90">Enable GPS under
            <a href="{{ route('admin.tms.settings.index', ['factory_id' => $factoryId]) }}" class="font-semibold underline">TMS Settings</a>
            and choose <strong>Driver Mobile GPS</strong> or <strong>Telematics API</strong>.
        </p>
    </div>
@endif

<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-4 gap-3 items-end">
    @if($factories !== [])
        <div>
            <label class="erp-label">Unit</label>
            <select name="factory_id" class="erp-input" onchange="this.form.submit()">
                <option value="">Select unit…</option>
                @foreach($factories as $id => $name)
                    <option value="{{ $id }}" @selected($factoryId == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @if($factoryId && $vehicles !== [])
        <div>
            <label class="erp-label">Vehicle</label>
            <select name="factory_id" value="{{ $factoryId }}" class="hidden">
                <option value="{{ $factoryId }}" selected></option>
            </select>
            <select name="vehicle_id" class="erp-input">
                <option value="">All vehicles</option>
                @foreach($vehicles as $id => $label)
                    <option value="{{ $id }}" @selected(($filters['vehicle_id'] ?? '') == $id)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit" class="erp-btn-primary">Apply</button>
        </div>
    @endif
</form>

@if($settings)
    <div class="erp-panel p-4 mb-4 text-sm grid sm:grid-cols-3 gap-4">
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Status</span>
            <span class="erp-badge {{ $settings->gps_tracking_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                {{ $settings->gps_tracking_enabled ? 'Enabled' : 'Disabled' }}
            </span>
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Provider</span>
            <span class="font-medium">{{ $providers[$settings->gps_provider]['label'] ?? ucfirst($settings->gps_provider) }}</span>
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Positions stored</span>
            <span class="font-medium tabular-nums">{{ $positions instanceof \Illuminate\Contracts\Pagination\Paginator ? $positions->total() : $positions->count() }}</span>
        </div>
    </div>
@endif

<div class="erp-panel overflow-hidden">
    <table class="erp-table text-sm">
        <thead>
            <tr>
                <th>Recorded</th>
                <th>Vehicle</th>
                <th>Trip</th>
                <th>Coordinates</th>
                <th class="text-right">Speed</th>
                <th>Source</th>
            </tr>
        </thead>
        <tbody>
            @forelse($positions as $pos)
                <tr>
                    <td class="tabular-nums whitespace-nowrap">@portalDateTime($pos->recorded_at)</td>
                    <td>{{ $pos->vehicle?->displayLabel() ?? '—' }}</td>
                    <td>
                        @if($pos->trip_log_id)
                            <a href="{{ route('admin.tms.trips.show', $pos->trip_log_id) }}" class="text-indigo-600">#{{ $pos->trip_log_id }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="tabular-nums text-xs">
                        <a href="{{ $pos->googleMapsUrl() }}" target="_blank" rel="noopener" class="text-indigo-600">{{ $pos->coordinatesLabel() }}</a>
                    </td>
                    <td class="text-right tabular-nums">{{ $pos->speed_kmh !== null ? number_format((float) $pos->speed_kmh, 1) . ' km/h' : '—' }}</td>
                    <td><span class="erp-badge bg-gray-100 text-gray-600">{{ $pos->source }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-10 text-gray-400">
                        No GPS positions recorded yet.
                        @if($settings && ! $settings->gps_tracking_enabled)
                            Enable tracking in TMS Settings to prepare for device integration.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if($positions instanceof \Illuminate\Contracts\Pagination\Paginator && $positions->hasPages())
        <div class="px-4 py-3 border-t">{{ $positions->links() }}</div>
    @endif
</div>
@endsection
