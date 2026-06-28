@extends('layouts.admin')
@section('title', $vehicle->exists ? 'Edit Vehicle' : 'Add Vehicle')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $vehicle->exists ? 'Edit Vehicle' : 'Add Vehicle',
    'actions' => '<a href="' . route('admin.tms.vehicles.index') . '" class="erp-btn-secondary">← Back</a>',
])
<div class="erp-panel p-6 max-w-2xl">
<form method="POST" action="{{ $vehicle->exists ? route('admin.tms.vehicles.update', $vehicle) : route('admin.tms.vehicles.store') }}" class="space-y-4">
@csrf @if($vehicle->exists) @method('PUT') @endif
<div><label class="erp-label">Unit</label><select name="factory_id" class="erp-input" required>@foreach($factories as $id => $name)<option value="{{ $id }}" @selected(old('factory_id', $vehicle->factory_id) == $id)>{{ $name }}</option>@endforeach</select></div>
<div class="grid grid-cols-2 gap-4">
<div><label class="erp-label">Name</label><input type="text" name="name" class="erp-input" value="{{ old('name', $vehicle->name) }}" required></div>
<div><label class="erp-label">Registration</label><input type="text" name="reg_number" class="erp-input" value="{{ old('reg_number', $vehicle->reg_number) }}" required></div>
</div>
<div class="grid grid-cols-3 gap-4">
<div><label class="erp-label">Type</label><select name="type" class="erp-input">@foreach($types as $k => $l)<option value="{{ $k }}" @selected(old('type', $vehicle->type) === $k)>{{ $l }}</option>@endforeach</select></div>
<div><label class="erp-label">Fuel</label><select name="fuel_type" class="erp-input">@foreach($fuelTypes as $k => $l)<option value="{{ $k }}" @selected(old('fuel_type', $vehicle->fuel_type) === $k)>{{ $l }}</option>@endforeach</select></div>
<div><label class="erp-label">Capacity</label><input type="number" name="passenger_capacity" class="erp-input" value="{{ old('passenger_capacity', $vehicle->passenger_capacity) }}" min="1" required></div>
</div>
<div><label class="erp-label">Status</label><select name="status" class="erp-input">@foreach($statuses as $k => $l)<option value="{{ $k }}" @selected(old('status', $vehicle->status) === $k)>{{ $l }}</option>@endforeach</select></div>
<div class="border-t pt-4"><p class="text-xs font-semibold text-gray-500 mb-2">Rental (if applicable)</p>
<div class="grid grid-cols-2 gap-4">
<div><label class="erp-label">Company</label><input type="text" name="rental_company" class="erp-input" value="{{ old('rental_company', $vehicle->rental_company) }}"></div>
<div><label class="erp-label">Amount</label><input type="number" step="0.01" name="rental_amount" class="erp-input" value="{{ old('rental_amount', $vehicle->rental_amount) }}"></div>
<div><label class="erp-label">Fuel Covered By</label><select name="fuel_covered_by" class="erp-input"><option value="">—</option>@foreach($paidBy as $k => $l)<option value="{{ $k }}" @selected(old('fuel_covered_by', $vehicle->fuel_covered_by) === $k)>{{ $l }}</option>@endforeach</select></div>
<div><label class="erp-label">Maintenance Covered By</label><select name="maintenance_covered_by" class="erp-input"><option value="">—</option>@foreach($paidBy as $k => $l)<option value="{{ $k }}" @selected(old('maintenance_covered_by', $vehicle->maintenance_covered_by) === $k)>{{ $l }}</option>@endforeach</select></div>
</div></div>
<div class="flex gap-2 pt-2">
<button type="submit" class="erp-btn-primary">Save</button>
</div>
</form>
</div>
@endsection
