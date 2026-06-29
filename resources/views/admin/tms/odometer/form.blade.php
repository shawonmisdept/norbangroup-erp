@extends('layouts.admin')
@section('title', 'Edit Daily KM')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Edit Daily KM',
    'subtitle' => 'Authority correction — update morning or evening readings',
    'actions' => '<a href="' . route('admin.tms.odometer.index') . '" class="erp-btn-secondary">← Back</a>',
])
<div class="erp-panel p-6 max-w-xl">
<form method="POST" action="{{ route('admin.tms.odometer.update', $log) }}" class="space-y-4">
@csrf @method('PUT')
<div><label class="erp-label">Unit</label><select name="factory_id" class="erp-input" required>@foreach($factories as $id => $name)<option value="{{ $id }}" @selected(old('factory_id', $log->factory_id) == $id)>{{ $name }}</option>@endforeach</select></div>
<div><label class="erp-label">Vehicle</label><select name="vehicle_id" class="erp-input" required>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected(old('vehicle_id', $log->vehicle_id) == $v->id)>{{ $v->displayLabel() }}</option>@endforeach</select></div>
<div><label class="erp-label">Date</label><input type="date" name="log_date" class="erp-input" value="{{ old('log_date', $log->log_date?->format('Y-m-d')) }}" required></div>
<div class="grid grid-cols-2 gap-4">
<div>
    <label class="erp-label">Morning KM</label>
    <input type="number" step="0.01" min="0" name="morning_km" class="erp-input" value="{{ old('morning_km', $log->morning_km) }}">
    @if($log->morningRecordedTime())<p class="text-[10px] text-gray-400 mt-1">{{ $log->morningRecordedTime() }}</p>@endif
</div>
<div>
    <label class="erp-label">Evening KM</label>
    <input type="number" step="0.01" min="0" name="evening_km" class="erp-input" value="{{ old('evening_km', $log->evening_km) }}">
    @if($log->eveningRecordedTime())<p class="text-[10px] text-gray-400 mt-1">{{ $log->eveningRecordedTime() }}</p>@endif
</div>
</div>
<div><label class="erp-label">Notes</label><textarea name="notes" class="erp-input" rows="2">{{ old('notes', $log->notes) }}</textarea></div>
<div class="flex gap-2 pt-2">
<button type="submit" class="erp-btn-primary">Update</button>
</div>
</form>
</div>
@endsection
