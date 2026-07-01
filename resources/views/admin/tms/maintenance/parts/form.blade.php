@extends('layouts.admin')
@section('title', $part->exists ? 'Edit Part' : 'Add Part')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $part->exists ? 'Edit Part' : 'Add Part',
    'actions' => '<a href="' . route('admin.tms.maintenance.parts.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="erp-panel p-6 max-w-xl">
    <form method="POST" action="{{ $part->exists ? route('admin.tms.maintenance.parts.update', $part) : route('admin.tms.maintenance.parts.store') }}" class="space-y-4">
        @csrf
        @if($part->exists)
            @method('PUT')
        @endif

        <div>
            <label class="erp-label">Unit</label>
            <select name="factory_id" class="erp-input" required>
                @foreach($factories as $id => $name)
                    <option value="{{ $id }}" @selected(old('factory_id', $part->factory_id) == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="erp-label">Part / Service Name</label>
            <input type="text" name="name" class="erp-input" value="{{ old('name', $part->name) }}" required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="erp-label">Unit</label>
                <select name="unit" class="erp-input">
                    <option value="">—</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit }}" @selected(old('unit', $part->unit) === $unit)>{{ $unit }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-label">Default Unit Price (BDT)</label>
                <input type="number" step="0.01" min="0" name="default_unit_price" class="erp-input" value="{{ old('default_unit_price', $part->default_unit_price) }}">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $part->is_active ?? true))>
            Active
        </label>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="erp-btn-primary">Save</button>
        </div>
    </form>
</div>
@endsection
