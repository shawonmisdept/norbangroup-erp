@extends('layouts.admin')
@section('title', $log->exists ? 'Edit Maintenance' : 'Add Maintenance')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $log->exists ? 'Edit Maintenance Log' : 'Add Maintenance Log',
    'actions' => '<a href="' . route('admin.tms.maintenance.index') . '" class="erp-btn-secondary">← Back</a>',
])
<div class="erp-panel p-6 max-w-3xl">
<form method="POST" action="{{ $log->exists ? route('admin.tms.maintenance.update', $log) : route('admin.tms.maintenance.store') }}" class="space-y-4">
@csrf @if($log->exists) @method('PUT') @endif
<div class="grid grid-cols-2 gap-4">
<div><label class="erp-label">Unit</label><select name="factory_id" class="erp-input" required>@foreach($factories as $id => $name)<option value="{{ $id }}" @selected(old('factory_id', $log->factory_id) == $id)>{{ $name }}</option>@endforeach</select></div>
<div><label class="erp-label">Vehicle</label><select name="vehicle_id" class="erp-input" required>@foreach($vehicles as $id => $label)<option value="{{ $id }}" @selected(old('vehicle_id', $log->vehicle_id) == $id)>{{ $label }}</option>@endforeach</select></div>
</div>
<div class="grid grid-cols-3 gap-4">
<div><label class="erp-label">Service Date</label><input type="date" name="service_date" class="erp-input" value="{{ old('service_date', $log->service_date?->format('Y-m-d')) }}" required></div>
<div><label class="erp-label">Odometer (KM)</label><input type="number" step="0.01" min="0" name="odometer_km" class="erp-input" value="{{ old('odometer_km', $log->odometer_km) }}"></div>
<div><label class="erp-label">Service Type</label><select name="service_type" class="erp-input">@foreach($serviceTypes as $k => $l)<option value="{{ $k }}" @selected(old('service_type', $log->service_type) === $k)>{{ $l }}</option>@endforeach</select></div>
</div>
<div class="grid grid-cols-2 gap-4">
<div><label class="erp-label">Vendor</label><input type="text" name="vendor_name" class="erp-input" value="{{ old('vendor_name', $log->vendor_name) }}"></div>
<div><label class="erp-label">Paid By</label><select name="paid_by" class="erp-input">@foreach($paidBy as $k => $l)<option value="{{ $k }}" @selected(old('paid_by', $log->paid_by) === $k)>{{ $l }}</option>@endforeach</select></div>
</div>
<div><label class="erp-label">Description</label><textarea name="description" class="erp-input" rows="2">{{ old('description', $log->description) }}</textarea></div>
<div class="grid grid-cols-2 gap-4">
<div><label class="erp-label">Labor Cost (BDT)</label><input type="number" step="0.01" min="0" name="labor_cost" class="erp-input" value="{{ old('labor_cost', $log->labor_cost) }}"></div>
<div><label class="erp-label">Status</label><select name="status" class="erp-input">@foreach($statuses as $k => $l)<option value="{{ $k }}" @selected(old('status', $log->status) === $k)>{{ $l }}</option>@endforeach</select></div>
</div>

<div class="border-t pt-4">
<div class="flex items-center justify-between mb-2">
<p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Parts</p>
<button type="button" id="add-part-row" class="erp-btn-sm-secondary">+ Add Part</button>
</div>
<div id="parts-container" class="space-y-2">
@php $parts = old('parts', $log->exists ? $log->parts->map(fn($p) => ['part_name' => $p->part_name, 'quantity' => $p->quantity, 'unit_price' => $p->unit_price])->all() : [['part_name' => '', 'quantity' => 1, 'unit_price' => '']]); @endphp
@foreach($parts as $i => $part)
<div class="grid grid-cols-12 gap-2 part-row">
<div class="col-span-5"><input type="text" name="parts[{{ $i }}][part_name]" class="erp-input" placeholder="Part name" value="{{ $part['part_name'] ?? '' }}"></div>
<div class="col-span-2"><input type="number" step="0.001" min="0" name="parts[{{ $i }}][quantity]" class="erp-input" placeholder="Qty" value="{{ $part['quantity'] ?? 1 }}"></div>
<div class="col-span-3"><input type="number" step="0.01" min="0" name="parts[{{ $i }}][unit_price]" class="erp-input" placeholder="Unit price" value="{{ $part['unit_price'] ?? '' }}"></div>
<div class="col-span-2 flex items-center"><button type="button" class="text-xs text-red-600 remove-part-row">Remove</button></div>
</div>
@endforeach
</div>
</div>

<div><label class="erp-label">Notes</label><textarea name="notes" class="erp-input" rows="2">{{ old('notes', $log->notes) }}</textarea></div>
<div class="flex gap-2 pt-2"><button type="submit" class="erp-btn-primary">Save</button></div>
</form>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('parts-container');
    let partIndex = {{ count($parts) }};

    document.getElementById('add-part-row')?.addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-2 part-row';
        row.innerHTML = `
            <div class="col-span-5"><input type="text" name="parts[${partIndex}][part_name]" class="erp-input" placeholder="Part name"></div>
            <div class="col-span-2"><input type="number" step="0.001" min="0" name="parts[${partIndex}][quantity]" class="erp-input" placeholder="Qty" value="1"></div>
            <div class="col-span-3"><input type="number" step="0.01" min="0" name="parts[${partIndex}][unit_price]" class="erp-input" placeholder="Unit price"></div>
            <div class="col-span-2 flex items-center"><button type="button" class="text-xs text-red-600 remove-part-row">Remove</button></div>`;
        container.appendChild(row);
        partIndex++;
    });

    container?.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-part-row')) {
            e.target.closest('.part-row')?.remove();
        }
    });
});
</script>
@endsection
