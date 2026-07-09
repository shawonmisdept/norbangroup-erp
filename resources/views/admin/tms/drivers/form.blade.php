@extends('layouts.admin')
@section('title', $driver->exists ? 'Edit Driver' : 'Add Driver')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $driver->exists ? 'Edit Driver' : 'Add Driver',
    'actions' => '<a href="' . route('admin.tms.drivers.index') . '" class="erp-btn-secondary">← Back</a>',
])

@php
    $selectedVehicleIds = array_map('intval', (array) old('vehicle_ids', $assignedVehicleIds));
    $selectedPrimaryId = (int) old('primary_vehicle_id', $primaryVehicleId ?? 0);
@endphp

<div class="erp-panel p-6 max-w-xl">
    <form method="POST" action="{{ $driver->exists ? route('admin.tms.drivers.update', $driver) : route('admin.tms.drivers.store') }}" class="space-y-4">
        @csrf
        @if($driver->exists)
            @method('PUT')
        @endif

        <div>
            <label class="erp-label">Unit</label>
            <select name="factory_id" class="erp-input" required>
                @foreach($factories as $id => $name)
                    <option value="{{ $id }}" @selected(old('factory_id', $driver->factory_id) == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="erp-label">Employee</label>
            <select name="employee_id" class="erp-input" required>
                @foreach($employees as $id => $name)
                    <option value="{{ $id }}" @selected(old('employee_id', $driver->employee_id) == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="erp-label">Assigned Vehicles</label>
            <p class="text-xs text-gray-500 mb-2">Select every vehicle this driver may operate. Mark one as primary.</p>
            <div class="space-y-2 rounded border border-erp-border p-3 max-h-64 overflow-y-auto">
                @foreach($vehicles as $id => $label)
                    @php
                        $vehicleId = (int) $id;
                        $isChecked = in_array($vehicleId, $selectedVehicleIds, true);
                    @endphp
                    <label class="flex items-start gap-3 text-sm">
                        <input
                            type="checkbox"
                            name="vehicle_ids[]"
                            value="{{ $vehicleId }}"
                            class="driver-vehicle-check mt-0.5 rounded border-gray-300"
                            @checked($isChecked)
                        >
                        <span class="flex-1 min-w-0">
                            <span class="block">{{ $label }}</span>
                            <label class="inline-flex items-center gap-1.5 mt-1 text-xs text-gray-500">
                                <input
                                    type="radio"
                                    name="primary_vehicle_id"
                                    value="{{ $vehicleId }}"
                                    class="driver-vehicle-primary rounded-full border-gray-300"
                                    @checked($selectedPrimaryId === $vehicleId)
                                    @disabled(! $isChecked)
                                >
                                Primary vehicle
                            </label>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('vehicle_ids')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            @error('vehicle_ids.*')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            @error('primary_vehicle_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="erp-label">License Number</label>
            <input type="text" name="license_number" class="erp-input" value="{{ old('license_number', $driver->license_number) }}">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="erp-label">OT Rate (BDT/hr)</label>
                <input type="number" step="0.01" name="ot_rate" class="erp-input" value="{{ old('ot_rate', $driver->ot_rate) }}" required>
            </div>
            <div>
                <label class="erp-label">OT Rate Effective From</label>
                <input type="date" name="ot_rate_effective_from" class="erp-input" value="{{ old('ot_rate_effective_from', $driver->ot_rate_effective_from?->format('Y-m-d')) }}">
            </div>
        </div>

        <div>
            <label class="erp-label">Status</label>
            <select name="status" class="erp-input">
                <option value="active" @selected(old('status', $driver->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $driver->status) === 'inactive')>Inactive</option>
            </select>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_overtime_active" value="1" @checked(old('is_overtime_active', $driver->is_overtime_active))>
            Overtime Active
        </label>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="erp-btn-primary">Save</button>
        </div>
    </form>

    @if($driver->exists && $driver->otRateLogs->isNotEmpty())
        <div class="mt-8 pt-6 border-t border-erp-border">
            <h3 class="font-semibold mb-3">OT Rate History</h3>
            <div class="overflow-x-auto">
                <table class="erp-table tms-registry-table text-sm">
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
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const syncPrimaryRadios = () => {
        const checkedIds = new Set(
            [...document.querySelectorAll('.driver-vehicle-check:checked')].map((el) => el.value)
        );

        let hasEnabledPrimary = false;

        document.querySelectorAll('.driver-vehicle-primary').forEach((radio) => {
            const enabled = checkedIds.has(radio.value);
            radio.disabled = !enabled;

            if (!enabled && radio.checked) {
                radio.checked = false;
            }

            if (enabled && radio.checked) {
                hasEnabledPrimary = true;
            }
        });

        if (!hasEnabledPrimary && checkedIds.size > 0) {
            const firstChecked = document.querySelector('.driver-vehicle-check:checked');
            const primary = document.querySelector(`.driver-vehicle-primary[value="${firstChecked.value}"]`);
            if (primary) {
                primary.disabled = false;
                primary.checked = true;
            }
        }
    };

    document.querySelectorAll('.driver-vehicle-check, .driver-vehicle-primary').forEach((el) => {
        el.addEventListener('change', syncPrimaryRadios);
    });

    syncPrimaryRadios();
});
</script>
@endsection
