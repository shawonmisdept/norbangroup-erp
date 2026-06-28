<div class="erp-panel p-6">
<h3 class="font-semibold mb-3">Approve & Assign</h3>
<form method="POST" action="{{ route('admin.tms.requests.approve', $transportRequest) }}" class="space-y-3" id="approve-form">
@csrf
<div><label class="erp-label">Driver (who will actually drive)</label>
<select name="driver_id" id="approve-driver" class="erp-input" required>
<option value="">Select…</option>
@foreach($drivers as $d)
<option value="{{ $d->id }}" data-vehicle="{{ $d->default_vehicle_id }}" data-capacity="{{ $d->defaultVehicle?->passenger_capacity ?? 0 }}">
{{ $d->displayLabel() }} — default: {{ $d->defaultVehicle?->displayLabel() ?? 'No vehicle' }}
</option>
@endforeach
</select>
</div>
<div><label class="erp-label">Vehicle</label>
<select name="vehicle_id" id="approve-vehicle" class="erp-input">
<option value="">Use driver's default vehicle</option>
@foreach($vehicles as $v)
<option value="{{ $v->id }}" data-capacity="{{ $v->passenger_capacity }}">{{ $v->displayLabel() }} ({{ $v->passenger_capacity }} seats)</option>
@endforeach
</select>
<p class="text-xs text-gray-500 mt-1">Change vehicle if another car is used today. Passengers: {{ $transportRequest->passenger_count }}</p>
</div>
<button type="submit" class="erp-btn-primary w-full">Approve</button>
</form>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const driver = document.getElementById('approve-driver');
    const vehicle = document.getElementById('approve-vehicle');
    driver?.addEventListener('change', () => {
        const defaultVehicleId = driver.selectedOptions[0]?.dataset.vehicle;
        if (defaultVehicleId && vehicle) {
            vehicle.value = defaultVehicleId;
        }
    });
});
</script>
