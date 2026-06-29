@extends('layouts.rental')
@section('title', 'Record Evening KM')
@section('page-title', 'Record Evening KM')
@section('page-subtitle', $log->log_date?->format('d M Y'))
@section('back', route('rental.odometer'))
@section('content')
<div class="emp-card p-4 space-y-4 max-w-lg">
<div class="rounded-lg bg-gray-50 border border-gray-100 p-3 text-sm">
<p class="text-xs text-gray-500 uppercase tracking-wide">Morning KM recorded</p>
<p class="text-lg font-semibold tabular-nums mt-1">{{ number_format($log->morning_km, 2) }}</p>
@if($log->morningRecordedTime())<p class="text-xs text-gray-400 mt-1">{{ $log->morningRecordedTime() }}</p>@endif
<p class="text-xs text-gray-500 mt-2">{{ $vehicle->displayLabel() }}</p>
</div>
<form method="POST" action="{{ route('rental.odometer.evening.store', $log) }}" class="space-y-3">
@csrf
<div>
<label class="text-xs text-gray-600">Evening KM</label>
<input type="number" step="0.01" min="{{ $log->morning_km }}" name="evening_km" class="emp-input w-full mt-1" value="{{ old('evening_km') }}" required autofocus placeholder="Min {{ number_format($log->morning_km, 2) }}">
</div>
<div>
<label class="text-xs text-gray-600">Notes</label>
<textarea name="notes" class="emp-input w-full mt-1" rows="2">{{ old('notes', $log->notes) }}</textarea>
</div>
<button type="submit" class="emp-btn w-full">Save Evening KM</button>
</form>
</div>
@endsection
