<div class="space-y-3">
    <div>
        <label class="erp-label">Driver Type</label>
        <select name="driver_type" class="erp-input driver-type-select" required>
            <option value="company" @selected(old('driver_type', 'company') === 'company')>Company Driver (Employee)</option>
            <option value="rental" @selected(old('driver_type') === 'rental')>Rental Driver (External)</option>
        </select>
    </div>

    <div class="company-driver-field">
        <label class="erp-label">Company Driver</label>
        <select name="driver_id" class="erp-input company-driver-select">
            <option value="">Select…</option>
            @foreach($drivers as $d)
                <option value="{{ $d->id }}" data-vehicle="{{ $d->default_vehicle_id }}" data-capacity="{{ $d->defaultVehicle?->passenger_capacity ?? 0 }}" @selected(old('driver_id') == $d->id)>
                    {{ $d->displayLabel() }} — default: {{ $d->defaultVehicle?->displayLabel() ?? 'No vehicle' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="rental-driver-field hidden">
        <label class="erp-label">Rental Driver</label>
        <select name="rental_driver_id" class="erp-input rental-driver-select">
            <option value="">Select…</option>
            @foreach($rentalDrivers as $d)
                <option value="{{ $d->id }}" data-vehicle="{{ $d->default_vehicle_id }}" data-capacity="{{ $d->defaultVehicle?->passenger_capacity ?? 0 }}" @selected(old('rental_driver_id') == $d->id)>
                    {{ $d->displayLabel() }} — default: {{ $d->defaultVehicle?->displayLabel() ?? 'No vehicle' }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="erp-label">Vehicle</label>
        <select name="vehicle_id" class="erp-input assign-vehicle-select">
            <option value="">Use driver's default vehicle</option>
            @foreach($vehicles as $v)
                <option value="{{ $v->id }}" data-capacity="{{ $v->passenger_capacity }}" data-warnings="{{ json_encode(($vehiclePaperWarnings ?? [])[$v->id] ?? []) }}" @selected(old('vehicle_id') == $v->id)>
                    {{ $v->displayLabel() }} ({{ $v->passenger_capacity }} seats)
                </option>
            @endforeach
        </select>
        <div id="vehicle-paper-warning" class="hidden mt-2 rounded border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900"></div>
        @if(isset($passengerCount))
            <p class="text-xs text-gray-500 mt-1">Passengers: {{ $passengerCount }}</p>
        @endif
    </div>
</div>
