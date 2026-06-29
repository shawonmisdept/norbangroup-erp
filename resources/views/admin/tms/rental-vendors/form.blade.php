@extends('layouts.admin')
@section('title', $vendor->exists ? 'Edit Rental Vendor' : 'Add Rental Vendor')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $vendor->exists ? 'Edit Rental Vendor' : 'Add Rental Vendor',
    'actions' => '<a href="' . route('admin.tms.rental-vendors.index') . '" class="erp-btn-secondary">← Back</a>',
])
<div class="erp-panel p-6 max-w-xl">
<form method="POST" action="{{ $vendor->exists ? route('admin.tms.rental-vendors.update', $vendor) : route('admin.tms.rental-vendors.store') }}" class="space-y-4">
@csrf @if($vendor->exists) @method('PUT') @endif
<div><label class="erp-label">Unit</label>
<select name="factory_id" class="erp-input" required>@foreach($factories as $id => $name)<option value="{{ $id }}" @selected(old('factory_id', $vendor->factory_id) == $id)>{{ $name }}</option>@endforeach</select></div>
<div><label class="erp-label">Vendor Name</label><input type="text" name="name" class="erp-input" value="{{ old('name', $vendor->name) }}" required></div>
<div class="grid grid-cols-2 gap-4">
<div><label class="erp-label">Contact Person</label><input type="text" name="contact_person" class="erp-input" value="{{ old('contact_person', $vendor->contact_person) }}"></div>
<div><label class="erp-label">Mobile</label><input type="text" name="mobile" class="erp-input" value="{{ old('mobile', $vendor->mobile) }}"></div>
</div>
<div><label class="erp-label">KM Rate (BDT/km)</label><input type="number" step="0.01" min="0" name="rental_km_rate" class="erp-input" value="{{ old('rental_km_rate', $vendor->rental_km_rate) }}" placeholder="Leave blank for factory default"></div>
<div><label class="erp-label">Status</label>
<select name="status" class="erp-input"><option value="active" @selected(old('status', $vendor->status) === 'active')>Active</option><option value="inactive" @selected(old('status', $vendor->status) === 'inactive')>Inactive</option></select></div>
<div class="flex gap-2 pt-2">
<button type="submit" class="erp-btn-primary">Save</button>
</div>
</form>
</div>
@endsection
