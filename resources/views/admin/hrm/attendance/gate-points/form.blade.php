@extends('layouts.admin')

@section('title', $point->exists ? 'Edit Gate Point' : 'New Gate Point')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.attendance.gate-points.index') }}" class="hover:text-brand">Gate QR</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $point->exists ? 'Edit' : 'New' }}</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'gate-points'])

@include('partials.erp.page-header', [
    'title' => $point->exists ? 'Edit Gate Point' : 'New Gate Point',
    'subtitle' => 'Set GPS coordinates for geofence validation at this gate',
])

<div class="erp-panel max-w-lg">
    <div class="erp-panel-body">
        <form method="POST"
              action="{{ $point->exists ? route('admin.hrm.attendance.gate-points.update', $point) : route('admin.hrm.attendance.gate-points.store') }}"
              class="space-y-4">
            @csrf
            @if($point->exists) @method('PUT') @endif

            @if(count($factories) > 1)
                <div>
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" required class="erp-input !text-xs">
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ old('factory_id', $point->factory_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="factory_id" value="{{ array_key_first($factories) }}">
            @endif

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="erp-form-label">Code</label>
                    <input type="text" name="code" value="{{ old('code', $point->code) }}" required placeholder="GATE-01" class="erp-input !text-xs font-mono">
                </div>
                <div>
                    <label class="erp-form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $point->name) }}" required placeholder="Main Gate" class="erp-input !text-xs">
                </div>
            </div>

            <div>
                <label class="erp-form-label">Location note</label>
                <input type="text" name="location" value="{{ old('location', $point->location) }}" placeholder="Factory entrance, north side" class="erp-input !text-xs">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="erp-form-label">Latitude</label>
                    <input type="text" name="latitude" value="{{ old('latitude', $point->latitude) }}" placeholder="23.8103" class="erp-input !text-xs">
                </div>
                <div>
                    <label class="erp-form-label">Longitude</label>
                    <input type="text" name="longitude" value="{{ old('longitude', $point->longitude) }}" placeholder="90.4125" class="erp-input !text-xs">
                </div>
            </div>

            <label class="flex items-center gap-2 text-xs">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $point->is_active) ? 'checked' : '' }} class="rounded border-gray-300">
                Active
            </label>

            <div class="flex gap-2">
                <button type="submit" class="erp-btn-primary">Save</button>
                <a href="{{ route('admin.hrm.attendance.gate-points.index') }}" class="erp-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
