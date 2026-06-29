@extends('layouts.admin')
@section('title', 'Record Morning KM')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Record Morning KM',
    'subtitle' => 'Save morning reading — evening can be recorded later from the list',
    'actions' => '<a href="' . route('admin.tms.odometer.index') . '" class="erp-btn-secondary">← Back</a>',
])
<div class="erp-panel p-6 max-w-xl">
<form method="POST" action="{{ route('admin.tms.odometer.morning.store') }}" class="space-y-4">
@csrf
<div><label class="erp-label">Unit</label><select name="factory_id" class="erp-input" required>@foreach($factories as $id => $name)<option value="{{ $id }}" @selected(old('factory_id', $log->factory_id ?? auth()->user()->factory_id) == $id)>{{ $name }}</option>@endforeach</select></div>
<div><label class="erp-label">Vehicle</label><select name="vehicle_id" class="erp-input" required>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected(old('vehicle_id', $log->vehicle_id) == $v->id)>{{ $v->displayLabel() }}</option>@endforeach</select></div>
<div><label class="erp-label">Date</label><input type="date" name="log_date" class="erp-input" value="{{ old('log_date', $log->log_date?->format('Y-m-d') ?? now()->toDateString()) }}" required></div>
<div><label class="erp-label">Morning KM</label><input type="number" step="0.01" min="0" name="morning_km" class="erp-input" value="{{ old('morning_km') }}" required autofocus></div>
<div><label class="erp-label">Notes</label><textarea name="notes" class="erp-input" rows="2">{{ old('notes') }}</textarea></div>
<div class="flex gap-2 pt-2">
<button type="submit" class="erp-btn-primary">Save Morning KM</button>
</div>
</form>
</div>
@endsection
