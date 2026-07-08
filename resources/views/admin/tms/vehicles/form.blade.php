@extends('layouts.admin')
@section('title', $vehicle->exists ? 'Edit Vehicle' : 'Add Vehicle')
@section('admin-content')
@php
    $cancelUrl = $vehicle->exists
        ? route('admin.tms.vehicles.show', $vehicle)
        : route('admin.tms.vehicles.index');
    $isRental = old('type', $vehicle->type) === 'rental';
@endphp
@include('partials.erp.page-header', [
    'title' => $vehicle->exists ? 'Edit Vehicle' : 'Add Vehicle',
    'subtitle' => $vehicle->exists ? $vehicle->displayLabel() : 'Register a new own or rental vehicle',
    'actions' => collect([
        '<a href="' . route('admin.tms.vehicles.index') . '" class="erp-btn-secondary">← Vehicles</a>',
        $vehicle->exists
            ? '<a href="' . route('admin.tms.vehicles.show', $vehicle) . '" class="erp-btn-secondary">View Profile</a>'
            : null,
        auth()->user()->canViewTmsSubmodule('vehicles')
            ? '<a href="' . route('admin.tms.vehicles.papers') . '" class="erp-btn-secondary">Papers Status</a>'
            : null,
    ])->filter()->implode(' '),
])

@if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 rounded-sm p-4 text-sm text-red-700">
        <p class="font-semibold mb-1">Please fix the following:</p>
        <ul class="list-disc list-inside space-y-0.5 text-xs">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="vehicle-form-layout">
    <form method="POST"
          action="{{ $vehicle->exists ? route('admin.tms.vehicles.update', $vehicle) : route('admin.tms.vehicles.store') }}"
          class="erp-panel erp-panel-form"
          id="vehicle-form">
        @csrf
        @if($vehicle->exists)
            @method('PUT')
        @endif

        <section id="vehicle-section-basic" class="vehicle-form-section">
            <div class="vehicle-form-section-head">
                <h3 class="text-sm font-semibold text-gray-800">Basic Information</h3>
                <p class="text-xs text-gray-400 mt-0.5">Identity, capacity, and operational status</p>
            </div>
            <div class="vehicle-form-section-body vehicle-form-grid">
                <div class="vehicle-form-field">
                    <label for="factory_id" class="erp-form-label">Unit <span class="text-red-500">*</span></label>
                    <select name="factory_id" id="factory_id" class="erp-input" required>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" @selected(old('factory_id', $vehicle->factory_id) == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('factory_id')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="name" class="erp-form-label">Vehicle Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" class="erp-input" value="{{ old('name', $vehicle->name) }}" required placeholder="e.g. Toyota Axio">
                    @error('name')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="vehicle_category" class="erp-form-label">Category</label>
                    <select name="vehicle_category" id="vehicle_category" class="erp-input">
                        <option value="">— Select —</option>
                        @foreach($categories as $k => $l)
                            <option value="{{ $k }}" @selected(old('vehicle_category', $vehicle->vehicle_category) === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                    @error('vehicle_category')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="reg_number" class="erp-form-label">Registration No. <span class="text-red-500">*</span></label>
                    <input type="text" name="reg_number" id="reg_number" class="erp-input" value="{{ old('reg_number', $vehicle->reg_number) }}" required placeholder="DM-GHA-22-1042">
                    @error('reg_number')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="model_year" class="erp-form-label">Model Year</label>
                    <input type="number" name="model_year" id="model_year" class="erp-input" value="{{ old('model_year', $vehicle->model_year) }}" min="1980" max="2100" placeholder="2022">
                    @error('model_year')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="vehicle-type" class="erp-form-label">Fleet Type <span class="text-red-500">*</span></label>
                    <select name="type" id="vehicle-type" class="erp-input">
                        @foreach($types as $k => $l)
                            <option value="{{ $k }}" @selected(old('type', $vehicle->type) === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                    @error('type')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="fuel_type" class="erp-form-label">Fuel Type <span class="text-red-500">*</span></label>
                    <select name="fuel_type" id="fuel_type" class="erp-input">
                        @foreach($fuelTypes as $k => $l)
                            <option value="{{ $k }}" @selected(old('fuel_type', $vehicle->fuel_type) === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                    @error('fuel_type')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="engine_cc" class="erp-form-label">Engine CC</label>
                    <input type="number" name="engine_cc" id="engine_cc" class="erp-input" value="{{ old('engine_cc', $vehicle->engine_cc) }}" min="50" max="10000" placeholder="1500">
                    @error('engine_cc')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="passenger_capacity" class="erp-form-label">Passenger Capacity <span class="text-red-500">*</span></label>
                    <input type="number" name="passenger_capacity" id="passenger_capacity" class="erp-input" value="{{ old('passenger_capacity', $vehicle->passenger_capacity) }}" min="1" required>
                    @error('passenger_capacity')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="status" class="erp-form-label">Operational Status <span class="text-red-500">*</span></label>
                    <select name="status" id="status" class="erp-input">
                        @foreach($statuses as $k => $l)
                            <option value="{{ $k }}" @selected(old('status', $vehicle->status) === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="is_dedicated" class="erp-form-label">Dedicated Use</label>
                    <label for="is_dedicated" class="vehicle-form-check">
                        <input type="checkbox" name="is_dedicated" id="is_dedicated" value="1" @checked(old('is_dedicated', $vehicle->is_dedicated))>
                        Executive vehicle
                    </label>
                </div>
            </div>
        </section>

        <section id="vehicle-section-rental"
                 class="vehicle-form-section rental-fields {{ $isRental ? '' : 'hidden' }}"
                 data-rental-section>
            <div class="vehicle-form-section-head">
                <h3 class="text-sm font-semibold text-gray-800">Rental Settings</h3>
                <p class="text-xs text-gray-400 mt-0.5">Vendor, rates, and cost coverage for rental fleet</p>
            </div>
            <div class="vehicle-form-section-body vehicle-form-grid">
                <div class="vehicle-form-field">
                    <label for="rental_vendor_id" class="erp-form-label">Rental Vendor</label>
                    <select name="rental_vendor_id" id="rental_vendor_id" class="erp-input">
                        <option value="">Select vendor…</option>
                        @foreach($vendors as $id => $name)
                            <option value="{{ $id }}" @selected(old('rental_vendor_id', $vehicle->rental_vendor_id) == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('rental_vendor_id')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="rental_km_rate" class="erp-form-label">KM Rate Override (BDT/km)</label>
                    <input type="number" step="0.01" min="0" name="rental_km_rate" id="rental_km_rate" class="erp-input" value="{{ old('rental_km_rate', $vehicle->rental_km_rate) }}" placeholder="Vendor default">
                    @error('rental_km_rate')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="fuel_covered_by" class="erp-form-label">Fuel Covered By</label>
                    <select name="fuel_covered_by" id="fuel_covered_by" class="erp-input">
                        <option value="">—</option>
                        @foreach($paidBy as $k => $l)
                            <option value="{{ $k }}" @selected(old('fuel_covered_by', $vehicle->fuel_covered_by) === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                    @error('fuel_covered_by')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="maintenance_covered_by" class="erp-form-label">Maintenance Covered By</label>
                    <select name="maintenance_covered_by" id="maintenance_covered_by" class="erp-input">
                        <option value="">—</option>
                        @foreach($paidBy as $k => $l)
                            <option value="{{ $k }}" @selected(old('maintenance_covered_by', $vehicle->maintenance_covered_by) === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                    @error('maintenance_covered_by')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section id="vehicle-section-purchase" class="vehicle-form-section">
            <div class="vehicle-form-section-head">
                <h3 class="text-sm font-semibold text-gray-800">Purchase &amp; Registration</h3>
                <p class="text-xs text-gray-400 mt-0.5">Asset purchase and registration dates</p>
            </div>
            <div class="vehicle-form-section-body vehicle-form-grid">
                <div class="vehicle-form-field">
                    <label for="purchase_date" class="erp-form-label">Date of Purchase</label>
                    <input type="date" name="purchase_date" id="purchase_date" class="erp-input" value="{{ old('purchase_date', $vehicle->purchase_date?->format('Y-m-d')) }}">
                    @error('purchase_date')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="registration_date" class="erp-form-label">Date of Registration</label>
                    <input type="date" name="registration_date" id="registration_date" class="erp-input" value="{{ old('registration_date', $vehicle->registration_date?->format('Y-m-d')) }}">
                    @error('registration_date')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="purchase_value" class="erp-form-label">Purchase Value (BDT)</label>
                    <input type="number" step="0.01" min="0" name="purchase_value" id="purchase_value" class="erp-input" value="{{ old('purchase_value', $vehicle->purchase_value) }}" placeholder="0.00">
                    @error('purchase_value')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section id="vehicle-section-papers" class="vehicle-form-section">
            <div class="vehicle-form-section-head flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Vehicle Papers</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Set current expiry dates. Renewal history can be logged from the vehicle profile after save.</p>
                </div>
                @if($vehicle->exists)
                    <a href="{{ route('admin.tms.vehicles.show', $vehicle) }}#renewal-form" class="text-xs font-medium text-brand hover:underline shrink-0">Record renewal →</a>
                @endif
            </div>
            <div class="vehicle-form-section-body vehicle-form-grid">
                    <div class="vehicle-form-field">
                        <label for="fitness_expires_at" class="erp-form-label">Fitness Expires</label>
                        <input type="date" name="fitness_expires_at" id="fitness_expires_at" class="erp-input" value="{{ old('fitness_expires_at', $vehicle->fitness_expires_at?->format('Y-m-d')) }}">
                        @error('fitness_expires_at')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="vehicle-form-field">
                        <label for="tax_token_expires_at" class="erp-form-label">Tax Token Expires</label>
                        <input type="date" name="tax_token_expires_at" id="tax_token_expires_at" class="erp-input" value="{{ old('tax_token_expires_at', $vehicle->tax_token_expires_at?->format('Y-m-d')) }}">
                        @error('tax_token_expires_at')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="vehicle-form-field">
                        <label for="insurance_expires_at" class="erp-form-label">Insurance Expires</label>
                        <input type="date" name="insurance_expires_at" id="insurance_expires_at" class="erp-input" value="{{ old('insurance_expires_at', $vehicle->insurance_expires_at?->format('Y-m-d')) }}">
                        @error('insurance_expires_at')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="vehicle-form-field">
                        <label for="route_permit_expires_at" class="erp-form-label">Route Permit Expires</label>
                        <input type="date" name="route_permit_expires_at" id="route_permit_expires_at" class="erp-input" value="{{ old('route_permit_expires_at', $vehicle->route_permit_expires_at?->format('Y-m-d')) }}">
                        @error('route_permit_expires_at')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="vehicle-form-field">
                        <label for="registration_paper_status" class="erp-form-label">Registration Paper Status</label>
                        <select name="registration_paper_status" id="registration_paper_status" class="erp-input">
                            @foreach($regStatuses as $k => $l)
                                <option value="{{ $k }}" @selected(old('registration_paper_status', $vehicle->registration_paper_status ?? 'ok') === $k)>{{ $l }}</option>
                            @endforeach
                        </select>
                        @error('registration_paper_status')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                    </div>
            </div>
        </section>

        <section id="vehicle-section-assignment" class="vehicle-form-section">
            <div class="vehicle-form-section-head">
                <h3 class="text-sm font-semibold text-gray-800">Assignment</h3>
                <p class="text-xs text-gray-400 mt-0.5">Allocated officer and primary driver</p>
            </div>
            <div class="vehicle-form-section-body vehicle-form-grid">
                <div class="vehicle-form-field">
                    <label for="allocated_employee_id" class="erp-form-label">Allocated User</label>
                    <select name="allocated_employee_id" id="allocated_employee_id" class="erp-input">
                        <option value="">— None —</option>
                        @foreach($employees as $id => $name)
                            <option value="{{ $id }}" @selected(old('allocated_employee_id', $vehicle->allocated_employee_id) == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('allocated_employee_id')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="vehicle-form-field">
                    <label for="primary_driver_id" class="erp-form-label">Primary Driver</label>
                    <select name="primary_driver_id" id="primary_driver_id" class="erp-input">
                        <option value="">— None —</option>
                        @foreach($drivers as $id => $name)
                            <option value="{{ $id }}" @selected(old('primary_driver_id', $vehicle->primary_driver_id) == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('primary_driver_id')<p class="vehicle-form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="vehicle-form-footer">
            <button type="submit" class="erp-btn-primary">
                {{ $vehicle->exists ? 'Save Changes' : 'Create Vehicle' }}
            </button>
            <a href="{{ $cancelUrl }}" class="erp-btn-secondary">Cancel</a>
            @if($vehicle->exists)
                <a href="{{ route('admin.tms.vehicles.show', $vehicle) }}" class="erp-btn-secondary ml-auto">View Profile</a>
            @endif
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.getElementById('vehicle-type');
    const rentalSection = document.querySelector('[data-rental-section]');
    const vendorSelect = document.getElementById('rental_vendor_id');

    const toggleRental = () => {
        const isRental = typeSelect?.value === 'rental';
        rentalSection?.classList.toggle('hidden', !isRental);
        if (vendorSelect) {
            vendorSelect.required = isRental;
        }
    };

    typeSelect?.addEventListener('change', toggleRental);
    toggleRental();
});
</script>
@endsection
