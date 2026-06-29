@extends('layouts.rental')
@section('title', 'Record Morning KM')
@section('page-title', 'Record Morning KM')
@section('page-subtitle', 'Evening can be recorded later from the list')
@section('back', route('rental.odometer'))
@section('content')
<div class="emp-card p-4 space-y-4 max-w-lg">
<div class="rounded-lg bg-gray-50 border border-gray-100 p-3 text-sm">
<p class="text-xs text-gray-500 uppercase tracking-wide">Vehicle</p>
<p class="font-semibold mt-1">{{ $vehicle->displayLabel() }}</p>
</div>
<form method="POST" action="{{ route('rental.odometer.morning.store') }}" class="space-y-3">
@csrf
<div>
<label class="text-xs text-gray-600">Date</label>
<input type="date" name="log_date" class="emp-input w-full mt-1" value="{{ old('log_date', $log->log_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
</div>
<div>
<label class="text-xs text-gray-600">Morning KM</label>
<input type="number" step="0.01" min="0" name="morning_km" class="emp-input w-full mt-1" value="{{ old('morning_km') }}" required autofocus placeholder="Morning KM">
</div>
<div>
<label class="text-xs text-gray-600">Notes</label>
<textarea name="notes" class="emp-input w-full mt-1" rows="2">{{ old('notes') }}</textarea>
</div>
<button type="submit" class="emp-btn w-full">Save Morning KM</button>
</form>
</div>
@endsection
