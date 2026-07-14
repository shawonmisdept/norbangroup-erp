@php
    $layout = $layout ?? 'horizontal';
    $isStack = $layout === 'stack';
@endphp

<div class="tms-assign-fields {{ $isStack ? 'tms-assign-fields--stack' : '' }} space-y-3">
    <div class="{{ $isStack ? 'space-y-3' : 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 gap-3 items-end' }}">
        <div class="{{ $isStack ? '' : 'xl:col-span-2' }}">
            <label class="erp-label">Driver Type</label>
            <select name="driver_type" class="erp-input driver-type-select" required>
                <option value="company" @selected(old('driver_type', 'company') === 'company')>Company Driver (Employee)</option>
                <option value="rental" @selected(old('driver_type') === 'rental')>Rental Driver (External)</option>
            </select>
        </div>

        <div class="company-driver-field {{ $isStack ? '' : 'sm:col-span-2 xl:col-span-4' }}">
            <label class="erp-label">Company Driver</label>
            <select name="driver_id" class="erp-input company-driver-select">
                <option value="">Select…</option>
                @foreach($drivers as $d)
                    @php
                        $primaryVehicleId = $d->primaryVehicleId();
                        $primaryVehicle = $d->vehicles->firstWhere('id', $primaryVehicleId) ?? $d->defaultVehicle;
                        $driverLabel = $d->assignmentSelectLabel();
                        if ($d->factory?->name) {
                            $driverLabel .= ' — ' . $d->factory->name;
                        }
                    @endphp
                    <option
                        value="{{ $d->id }}"
                        data-vehicle="{{ $primaryVehicleId }}"
                        data-capacity="{{ $primaryVehicle?->passenger_capacity ?? 0 }}"
                        data-assigned-vehicles="{{ json_encode($d->assignedVehicleIds()) }}"
                        @selected(old('driver_id') == $d->id)
                    >
                        {{ $driverLabel }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="rental-driver-field hidden {{ $isStack ? '' : 'sm:col-span-2 xl:col-span-4' }}">
            <label class="erp-label">Rental Driver</label>
            <select name="rental_driver_id" class="erp-input rental-driver-select">
                <option value="">Select…</option>
                @foreach($rentalDrivers as $d)
                    <option value="{{ $d->id }}" data-vehicle="{{ $d->default_vehicle_id }}" data-capacity="{{ $d->defaultVehicle?->passenger_capacity ?? 0 }}" @selected(old('rental_driver_id') == $d->id)>
                        {{ $d->displayLabel() }} — {{ $d->defaultVehicle?->displayLabel() ?? 'No vehicle' }}@if($d->factory?->name) — {{ $d->factory->name }}@endif
                    </option>
                @endforeach
            </select>
        </div>

        <div class="{{ $isStack ? '' : 'sm:col-span-2 xl:col-span-4' }}">
            <label class="erp-label">Vehicle</label>
            <select name="vehicle_id" class="erp-input assign-vehicle-select">
                <option value="">Use driver's primary vehicle</option>
                @foreach($vehicles as $v)
                    <option value="{{ $v->id }}" data-capacity="{{ $v->passenger_capacity }}" data-warnings="{{ json_encode(($vehiclePaperWarnings ?? [])[$v->id] ?? []) }}" @selected(old('vehicle_id') == $v->id)>
                        {{ $v->displayLabel() }} ({{ $v->passenger_capacity }} seats)@if($v->factory?->name) — {{ $v->factory->name }}@endif
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="vehicle-paper-warning hidden rounded border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900"></div>

    @if(isset($passengerCount))
        <p class="text-xs text-gray-500">Passengers: {{ $passengerCount }}</p>
    @endif
</div>
