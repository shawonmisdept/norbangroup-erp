@extends('layouts.admin')
@section('title', $driver->exists ? 'Edit Rental Driver' : 'Add Rental Driver')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $driver->exists ? 'Edit Rental Driver' : 'Add Rental Driver',
    'actions' => '<a href="' . route('admin.tms.rental-drivers.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="erp-panel p-6 max-w-2xl">
    <form method="POST" action="{{ $driver->exists ? route('admin.tms.rental-drivers.update', $driver) : route('admin.tms.rental-drivers.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @if($driver->exists)
            @method('PUT')
        @endif

        <div class="flex items-start gap-4 pb-4 border-b border-erp-border">
            @include('partials.rental-driver-avatar', ['driver' => $driver->exists ? $driver : null, 'size' => '180'])
            <div class="flex-1">
                <p class="erp-form-label !mb-0.5">Driver Photo</p>
                <p class="text-[11px] text-gray-500 mb-2">180 × 180 px — shown in rental portal and admin lists.</p>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"
                       class="w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-sm file:border-0 file:text-xs file:font-medium file:bg-orange-600 file:text-white">
                @error('photo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="erp-label">Unit</label>
            <select name="factory_id" class="erp-input" required>
                @foreach($factories as $id => $name)
                    <option value="{{ $id }}" @selected(old('factory_id', $driver->factory_id) == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="erp-label">Name</label>
                <input type="text" name="name" class="erp-input" value="{{ old('name', $driver->name) }}" required>
            </div>
            <div>
                <label class="erp-label">Mobile</label>
                <input type="text" name="mobile" class="erp-input" value="{{ old('mobile', $driver->mobile) }}">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="erp-label">NID Number</label>
                <input type="text" name="nid_number" class="erp-input" value="{{ old('nid_number', $driver->nid_number) }}">
            </div>
            <div>
                <label class="erp-label">License Number</label>
                <input type="text" name="license_number" class="erp-input" value="{{ old('license_number', $driver->license_number) }}">
            </div>
        </div>

        <div>
            <label class="erp-label">Vendor / Company</label>
            <select name="rental_vendor_id" class="erp-input">
                <option value="">— Select vendor —</option>
                @foreach($vendors as $id => $label)
                    <option value="{{ $id }}" @selected(old('rental_vendor_id', $driver->rental_vendor_id) == $id)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="erp-label">Default Vehicle</label>
            <select name="default_vehicle_id" class="erp-input">
                <option value="">—</option>
                @foreach($vehicles as $id => $label)
                    <option value="{{ $id }}" @selected(old('default_vehicle_id', $driver->default_vehicle_id) == $id)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="erp-label">Status</label>
            <select name="status" class="erp-input">
                <option value="active" @selected(old('status', $driver->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $driver->status) === 'inactive')>Inactive</option>
            </select>
        </div>

        <div>
            <label class="erp-label">Notes</label>
            <textarea name="notes" class="erp-input" rows="2">{{ old('notes', $driver->notes) }}</textarea>
        </div>

        <div class="border-t pt-4 space-y-3">
            <p class="font-semibold text-sm">Portal Access</p>
            <p class="text-xs text-gray-500">Rental drivers sign in at <code>/rental/login</code> with mobile + password.</p>

            @if($driver->portalUser)
                <p class="text-xs text-green-700">
                    Portal account active
                    @if($driver->portalUser->last_login_at)
                        · last login @portalDateTime($driver->portalUser->last_login_at)
                    @endif
                </p>
            @endif

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="enable_portal" value="1" @checked(old('enable_portal'))>
                Create portal account (auto password if blank below)
            </label>

            <div>
                <label class="erp-label">Portal Password</label>
                <input type="password" name="portal_password" class="erp-input" minlength="6" placeholder="{{ $driver->portalUser ? 'Leave blank to keep current' : 'Min 6 characters' }}">
            </div>
        </div>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="erp-btn-primary">Save</button>
        </div>
    </form>
</div>
@endsection
