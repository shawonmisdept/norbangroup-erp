@extends('layouts.admin')
@section('title', $driver->displayLabel())
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $driver->displayLabel(),
    'subtitle' => 'Company driver profile & recent trips',
    'actions' => '<a href="' . route('admin.tms.drivers.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 erp-panel p-6 space-y-4 text-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Unit</span>
                <span class="font-medium">{{ $driver->factory?->name ?? '—' }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Status</span>
                <span class="erp-badge {{ $driver->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                    {{ ucfirst($driver->status) }}
                </span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Employee Code</span>
                <span class="font-medium tabular-nums">{{ $driver->employee?->employee_code ?? '—' }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Phone</span>
                <span class="font-medium tabular-nums">{{ $driver->contactPhone() ?? '—' }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Designation</span>
                <span class="font-medium">{{ $driver->employee?->designation?->name ?? '—' }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Department</span>
                <span class="font-medium">{{ $driver->employee?->department?->name ?? '—' }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">License</span>
                <span class="font-medium tabular-nums">{{ $driver->license_number ?? '—' }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Default Vehicle</span>
                @if($driver->defaultVehicle)
                    <a href="{{ route('admin.tms.vehicles.show', $driver->defaultVehicle) }}" class="font-medium text-indigo-600">{{ $driver->defaultVehicle->displayLabel() }}</a>
                @else
                    <span class="font-medium">—</span>
                @endif
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">OT Rate</span>
                <span class="font-medium tabular-nums">৳{{ number_format((float) $driver->ot_rate, 2) }}/hr</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">OT Active</span>
                <span class="font-medium">{{ $driver->is_overtime_active ? 'Yes' : 'No' }}</span>
            </div>
            @if($driver->ot_rate_effective_from)
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">OT Rate Effective</span>
                    <span class="font-medium tabular-nums">{{ $driver->ot_rate_effective_from->format('d M Y') }}</span>
                </div>
            @endif
        </div>

        @if($canManage)
            <div class="pt-2 border-t border-erp-border">
                <a href="{{ route('admin.tms.drivers.edit', $driver) }}" class="erp-btn-sm-primary">Edit Driver</a>
            </div>
        @endif
    </div>

    <div class="erp-panel p-6">
        <h3 class="font-semibold mb-3">Recent Trips</h3>
        @forelse($recentTrips as $trip)
            <div class="py-2 border-b border-gray-100 last:border-0 text-sm">
                <a href="{{ route('admin.tms.trips.show', $trip) }}" class="font-medium text-indigo-600">Trip #{{ $trip->id }}</a>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $trip->vehicle?->displayLabel() ?? '—' }}
                    · {{ $trip->tripStatusLabel() }}
                </p>
            </div>
        @empty
            <p class="text-sm text-gray-400">No trips recorded yet.</p>
        @endforelse
    </div>
</div>

@if($driver->otRateLogs->isNotEmpty())
    <div class="erp-panel p-6 mt-4">
        <h3 class="font-semibold mb-3">OT Rate History</h3>
        <div class="overflow-x-auto">
            <table class="erp-table text-sm">
                <thead>
                    <tr>
                        <th>Recorded</th>
                        <th>Rate (BDT/hr)</th>
                        <th>Effective From</th>
                        <th>OT Active</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($driver->otRateLogs as $log)
                        <tr>
                            <td class="tabular-nums">@portalDateTime($log->created_at)</td>
                            <td class="tabular-nums">৳{{ number_format((float) $log->ot_rate, 2) }}</td>
                            <td class="tabular-nums">{{ $log->effective_from?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $log->is_overtime_active ? 'Yes' : 'No' }}</td>
                            <td>{{ $log->recordedByUser?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
