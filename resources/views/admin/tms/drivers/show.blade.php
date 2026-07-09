@extends('layouts.admin')
@section('title', $driver->displayLabel())
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $driver->displayLabel(),
    'subtitle' => 'Company driver profile & recent trips',
    'actions' => '<a href="' . route('admin.tms.drivers.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 erp-panel p-6 text-sm">
        <div class="tms-driver-detail-grid">
            <div class="tms-driver-detail-item">
                <span class="tms-driver-detail-label">Unit</span>
                <span class="font-medium">{{ $driver->factory?->name ?? '—' }}</span>
            </div>
            <div class="tms-driver-detail-item">
                <span class="tms-driver-detail-label">Status</span>
                <span class="erp-badge {{ $driver->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                    {{ ucfirst($driver->status) }}
                </span>
            </div>
            <div class="tms-driver-detail-item">
                <span class="tms-driver-detail-label">Employee Code</span>
                <span class="font-medium tabular-nums">{{ $driver->employee?->employee_code ?? '—' }}</span>
            </div>
            <div class="tms-driver-detail-item">
                <span class="tms-driver-detail-label">Phone</span>
                <span class="font-medium tabular-nums">{{ $driver->contactPhone() ?? '—' }}</span>
            </div>
            <div class="tms-driver-detail-item">
                <span class="tms-driver-detail-label">Designation</span>
                <span class="font-medium">{{ $driver->employee?->designation?->name ?? '—' }}</span>
            </div>
            <div class="tms-driver-detail-item">
                <span class="tms-driver-detail-label">Department</span>
                <span class="font-medium">{{ $driver->employee?->department?->name ?? '—' }}</span>
            </div>
            <div class="tms-driver-detail-item">
                <span class="tms-driver-detail-label">License</span>
                <span class="font-medium tabular-nums">{{ $driver->license_number ?? '—' }}</span>
            </div>
            <div class="tms-driver-detail-item">
                <span class="tms-driver-detail-label">OT Active</span>
                <span class="font-medium">{{ $driver->is_overtime_active ? 'Yes' : 'No' }}</span>
            </div>
            <div class="tms-driver-detail-item">
                <span class="tms-driver-detail-label">OT Rate</span>
                <span class="font-medium tabular-nums">৳{{ number_format((float) $driver->ot_rate, 2) }}/hr</span>
            </div>
            @if($driver->ot_rate_effective_from)
                <div class="tms-driver-detail-item">
                    <span class="tms-driver-detail-label">OT Rate Effective</span>
                    <span class="font-medium tabular-nums">{{ $driver->ot_rate_effective_from->format('d M Y') }}</span>
                </div>
            @endif
        </div>

        <div class="tms-driver-vehicles-section">
            <span class="tms-driver-detail-label">Assigned Vehicles</span>
            @if($driver->vehicles->isNotEmpty())
                <ul class="tms-driver-vehicles-list">
                    @foreach($driver->vehicles as $vehicle)
                        <li>
                            <a href="{{ route('admin.tms.vehicles.show', $vehicle) }}" class="font-medium text-indigo-600 hover:underline">
                                {{ $vehicle->displayLabel() }}
                            </a>
                            @if($vehicle->pivot?->is_primary)
                                <span class="text-xs text-gray-500">(primary)</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @elseif($driver->defaultVehicle)
                <a href="{{ route('admin.tms.vehicles.show', $driver->defaultVehicle) }}" class="font-medium text-indigo-600 hover:underline">{{ $driver->defaultVehicle->displayLabel() }}</a>
            @else
                <span class="font-medium">—</span>
            @endif
        </div>

        @if($canManage)
            <div class="pt-4 mt-4 border-t border-erp-border">
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
            <table class="erp-table tms-registry-table text-sm">
                <thead>
                    <tr>
                        <th>Recorded</th>
                        <th class="text-right">Rate (BDT/hr)</th>
                        <th>Effective From</th>
                        <th class="text-center">OT Active</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($driver->otRateLogs as $log)
                        <tr>
                            <td class="tabular-nums whitespace-nowrap">@portalDateTime($log->created_at)</td>
                            <td class="tabular-nums text-right whitespace-nowrap">৳{{ number_format((float) $log->ot_rate, 2) }}</td>
                            <td class="tabular-nums whitespace-nowrap">{{ $log->effective_from?->format('d M Y') ?? '—' }}</td>
                            <td class="text-center">{{ $log->is_overtime_active ? 'Yes' : 'No' }}</td>
                            <td>{{ $log->recordedByUser?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
