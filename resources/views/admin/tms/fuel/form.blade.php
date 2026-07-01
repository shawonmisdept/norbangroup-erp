@extends('layouts.admin')
@section('title', ($fuelLog->exists ? 'Edit' : 'Add') . ' Fuel Entry')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => ($fuelLog->exists ? 'Edit' : 'Add') . ' Fuel Entry',
    'actions' => '<a href="' . route('admin.tms.fuel.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="erp-panel p-6 max-w-xl">
    <form method="POST" action="{{ $fuelLog->exists ? route('admin.tms.fuel.update', $fuelLog) : route('admin.tms.fuel.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @if($fuelLog->exists) @method('PUT') @endif

        <div>
            <label class="erp-label">Unit</label>
            <select name="factory_id" class="erp-input" required>
                @foreach($factories as $id => $name)
                    <option value="{{ $id }}" {{ (string) old('factory_id', $fuelLog->factory_id) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="erp-label">Vehicle</label>
            <select name="vehicle_id" class="erp-input" required>
                @foreach($vehicles as $v)
                    <option value="{{ $v->id }}" {{ (string) old('vehicle_id', $fuelLog->vehicle_id) === (string) $v->id ? 'selected' : '' }}>{{ $v->displayLabel() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="erp-label">Trip (optional)</label>
            <select name="trip_log_id" class="erp-input">
                <option value="">—</option>
                @foreach($trips as $t)
                    <option value="{{ $t->id }}" {{ (string) old('trip_log_id', $fuelLog->trip_log_id) === (string) $t->id ? 'selected' : '' }}>#{{ $t->id }} · {{ $t->transportRequest?->employee?->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="erp-label">Fuel Type</label>
                <select name="fuel_type" class="erp-input">
                    @foreach($fuelTypes as $k => $l)
                        <option value="{{ $k }}" {{ old('fuel_type', $fuelLog->fuel_type) === $k ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-label">Unit</label>
                <input type="text" name="unit" class="erp-input" value="{{ old('unit', $fuelLog->unit ?? 'litre') }}">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="erp-label">Quantity</label>
                <input type="number" step="0.001" name="quantity" class="erp-input" value="{{ old('quantity', $fuelLog->quantity) }}" required>
            </div>
            <div>
                <label class="erp-label">Unit Price</label>
                <input type="number" step="0.01" name="unit_price" class="erp-input" value="{{ old('unit_price', $fuelLog->unit_price) }}" required>
            </div>
        </div>

        <div>
            <label class="erp-label">Paid By</label>
            <select name="paid_by" class="erp-input">
                @foreach($paidBy as $k => $l)
                    <option value="{{ $k }}" {{ old('paid_by', $fuelLog->paid_by) === $k ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="erp-label">Receipt Number</label>
            <input type="text" name="receipt_number" class="erp-input" value="{{ old('receipt_number', $fuelLog->receipt_number) }}">
        </div>

        <div>
            <label class="erp-label">Receipt Photo</label>
            <input type="file" name="receipt" class="erp-input" accept="image/*,.pdf">
            @if($fuelLog->receipt_path)
                <p class="text-xs text-gray-500 mt-1">Current receipt on file. Upload to replace.</p>
            @endif
        </div>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="erp-btn-primary">Save</button>
        </div>
    </form>
</div>
@endsection
