@extends('layouts.admin')
@section('title', $destination->exists ? 'Edit Destination' : 'Add Destination')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $destination->exists ? 'Edit Destination' : 'Add Destination',
    'subtitle' => 'Applies to every unit',
    'actions' => '<a href="' . route('admin.tms.destinations.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="erp-panel p-6 max-w-xl">
    <p class="text-sm text-gray-600 mb-6 rounded border border-erp-border bg-gray-50 px-3 py-2">
        This destination will be available for transport requests from <strong>all units</strong>.
    </p>

    <form method="POST" action="{{ $destination->exists ? route('admin.tms.destinations.update', $destination) : route('admin.tms.destinations.store') }}" class="space-y-4">
        @csrf
        @if($destination->exists)
            @method('PUT')
        @endif

        <div>
            <label class="erp-label">Name</label>
            <input type="text" name="name" class="erp-input" value="{{ old('name', $destination->name) }}" required>
        </div>

        <div>
            <label class="erp-label">Address</label>
            <input type="text" name="address" class="erp-input" value="{{ old('address', $destination->address) }}">
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $destination->is_active))>
            Active
        </label>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="erp-btn-primary">Save</button>
        </div>
    </form>
</div>
@endsection
