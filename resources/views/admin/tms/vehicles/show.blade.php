@extends('layouts.admin')
@section('title', $vehicle->displayLabel())
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $vehicle->name,
    'subtitle' => $vehicle->reg_number
        . ' · ' . ($vehicle->factory?->name ?? '—')
        . ' · ' . ucfirst($vehicle->type),
    'actions' => collect([
        '<a href="' . route('admin.tms.vehicles.index') . '" class="erp-btn-secondary">← Vehicles</a>',
        auth()->user()->canViewTmsSubmodule('vehicles')
            ? '<a href="' . route('admin.tms.vehicles.papers') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Papers Status</a>'
            : null,
        auth()->user()->canViewTmsSubmodule('maintenance')
            ? '<a href="' . route('admin.tms.maintenance.register', $vehicle) . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Maintenance</a>'
            : null,
        $canManage
            ? '<a href="' . route('admin.tms.vehicles.edit', $vehicle) . '" class="erp-btn-primary !py-2 !px-4 text-xs">Edit Vehicle</a>'
            : null,
    ])->filter()->implode(' '),
])

<div class="erp-panel p-4 mb-4 flex flex-wrap items-center gap-3">
    <span class="erp-badge {{ $vehicle->statusBadgeClass() }}">{{ $vehicle->statusLabel() }}</span>
    <span class="erp-badge {{ $paperService->statusBadgeClass($worstPaperStatus) }}">Papers: {{ ucfirst($worstPaperStatus) }}</span>
    @if($vehicle->is_dedicated)
        <span class="erp-badge bg-indigo-50 text-indigo-700">Dedicated</span>
    @endif
    @if($vehicle->isRental() && $vehicle->rentalVendor)
        <span class="text-xs text-gray-500">Vendor: <strong>{{ $vehicle->rentalVendor->name }}</strong></span>
    @endif
    <span class="text-xs text-gray-500 ml-auto tabular-nums">Last odometer: {{ number_format((float) $vehicle->last_odometer_km, 2) }} km</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="erp-panel p-6">
            <h3 class="text-sm font-semibold text-gray-800 border-b border-erp-border pb-2 mb-4">Vehicle Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Registration</span>
                    <span class="font-medium tabular-nums">{{ $vehicle->reg_number }}</span>
                </div>
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Category / Year</span>
                    <span class="font-medium">{{ config('tms.vehicle_categories.' . $vehicle->vehicle_category, $vehicle->vehicle_category ?? '—') }}@if($vehicle->model_year) · {{ $vehicle->model_year }} @endif</span>
                </div>
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Fuel / CC</span>
                    <span class="font-medium">{{ config('tms.fuel_types.' . $vehicle->fuel_type, $vehicle->fuel_type) ?: '—' }}@if($vehicle->engine_cc) · {{ number_format($vehicle->engine_cc) }} cc @endif</span>
                </div>
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Capacity</span>
                    <span class="font-medium tabular-nums">{{ $vehicle->passenger_capacity }} seats</span>
                </div>
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Purchase Date</span>
                    <span class="font-medium tabular-nums">{{ $vehicle->purchase_date?->format('d M Y') ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Registration Date</span>
                    <span class="font-medium tabular-nums">{{ $vehicle->registration_date?->format('d M Y') ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Purchase Value</span>
                    <span class="font-medium tabular-nums">{{ $vehicle->purchase_value ? '৳' . number_format((float) $vehicle->purchase_value, 0) : '—' }}</span>
                </div>
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Reg. Paper Status</span>
                    <span class="erp-badge {{ $vehicle->registration_paper_status === 'ok' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ config('tms.registration_paper_statuses.' . $vehicle->registration_paper_status, $vehicle->registration_paper_status) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="erp-panel p-6">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-erp-border pb-2 mb-4">
                <h3 class="text-sm font-semibold text-gray-800">Current Papers</h3>
                <a href="{{ route('admin.tms.vehicles.papers') }}" class="text-xs text-indigo-600 hover:underline">All vehicles →</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($papers as $paper)
                    <div class="rounded-lg border border-erp-border p-3 {{ $paperService->statusCellClass($paper['status']) }}">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">{{ $paper['label'] }}</p>
                        <p class="font-semibold text-sm tabular-nums mt-1">{{ $paper['expires_at']?->format('d M Y') ?? 'N/A' }}</p>
                        @if($paper['days_left'] !== null && $paper['status'] !== 'expired' && $paper['status'] !== 'na')
                            <p class="text-[10px] text-gray-500 mt-0.5">{{ $paper['days_left'] }} days left</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="erp-panel p-6">
            <h3 class="text-sm font-semibold text-gray-800 border-b border-erp-border pb-2 mb-4">Assignment</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Allocated User</span>
                    <span class="font-medium">{{ $vehicle->allocatedUserLabel() ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Primary Driver</span>
                    <span class="font-medium">
                        {{ $vehicle->assignedDriverNames() }}
                        @if($vehicle->primaryDriverContact())
                            <br>
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $vehicle->primaryDriverContact()) }}" class="text-indigo-600 text-xs">{{ $vehicle->primaryDriverContact() }}</a>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        @if($canManage)
            <div class="erp-panel p-6" id="renewal-form">
                <h3 class="text-sm font-semibold text-gray-800 border-b border-erp-border pb-2 mb-4">Record Paper Renewal</h3>
                <form method="POST" action="{{ route('admin.tms.vehicles.paper-renewals.store', $vehicle) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="erp-label">Paper Type <span class="text-red-500">*</span></label>
                            <select name="paper_type" class="erp-input" required>
                                @foreach($paperTypes as $k => $l)
                                    <option value="{{ $k }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="erp-label">New Expiry Date <span class="text-red-500">*</span></label>
                            <input type="date" name="new_expires_at" class="erp-input" required>
                        </div>
                        <div>
                            <label class="erp-label">Cost (BDT)</label>
                            <input type="number" step="0.01" min="0" name="cost" class="erp-input">
                        </div>
                        <div>
                            <label class="erp-label">Receipt No.</label>
                            <input type="text" name="receipt_number" class="erp-input" maxlength="64">
                        </div>
                    </div>
                    <div>
                        <label class="erp-label">Document (PDF/JPG, max 5MB)</label>
                        <input type="file" name="document" class="erp-input" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div>
                        <label class="erp-label">Notes</label>
                        <textarea name="notes" class="erp-input" rows="2" maxlength="2000"></textarea>
                    </div>
                    <button type="submit" class="erp-btn-primary">Save Renewal</button>
                </form>
            </div>
        @endif

        <div class="erp-panel p-6">
            <h3 class="text-sm font-semibold text-gray-800 border-b border-erp-border pb-2 mb-4">Renewal History</h3>
            <div class="overflow-x-auto">
                <table class="erp-table text-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Paper</th>
                            <th>Previous</th>
                            <th>New Expiry</th>
                            <th>Cost</th>
                            <th>By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehicle->paperRenewals as $renewal)
                            <tr>
                                <td class="text-xs tabular-nums whitespace-nowrap">{{ $renewal->renewed_at->format('d M Y') }}</td>
                                <td>{{ $renewal->paperTypeLabel() }}</td>
                                <td class="text-xs tabular-nums">{{ $renewal->previous_expires_at?->format('d M Y') ?? '—' }}</td>
                                <td class="text-xs tabular-nums">{{ $renewal->new_expires_at->format('d M Y') }}</td>
                                <td class="tabular-nums">{{ $renewal->cost ? '৳' . number_format((float) $renewal->cost, 0) : '—' }}</td>
                                <td class="text-xs">{{ $renewal->renewedByUser?->name ?? '—' }}</td>
                                <td class="text-right">
                                    @if($renewal->hasDocument())
                                        <a href="{{ route('admin.tms.vehicles.paper-renewals.document', $renewal) }}" class="erp-btn-sm-secondary">Download</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-6 text-gray-400">No renewals recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        @include('admin.tms.vehicles.partials.quick-actions', ['vehicle' => $vehicle, 'canManage' => $canManage])

        <div class="erp-panel p-5">
            <h3 class="text-sm font-semibold text-gray-800 border-b border-erp-border pb-2 mb-3">Recent Trips</h3>
            @forelse($recentTrips as $trip)
                <div class="py-2.5 border-b border-gray-100 last:border-0 text-sm">
                    <a href="{{ route('admin.tms.trips.show', $trip) }}" class="font-medium text-indigo-600 hover:underline">Trip #{{ $trip->id }}</a>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $trip->tripStatusLabel() }}
                        @if($trip->duty_start_at)
                            · {{ $trip->duty_start_at->format('d M Y') }}
                        @endif
                    </p>
                </div>
            @empty
                <p class="text-sm text-gray-400">No trips recorded yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
