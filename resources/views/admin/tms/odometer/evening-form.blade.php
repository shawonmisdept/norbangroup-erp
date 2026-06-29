@extends('layouts.admin')
@section('title', 'Record Evening KM')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Record Evening KM',
    'subtitle' => $log->vehicle?->displayLabel() . ' · ' . $log->log_date?->format('d M Y'),
    'actions' => '<a href="' . route('admin.tms.odometer.index') . '" class="erp-btn-secondary">← Back</a>',
])
<div class="erp-panel p-6 max-w-xl">
<div class="mb-4 p-3 rounded-lg bg-gray-50 border border-gray-100 text-sm">
    <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">Morning KM recorded</p>
    <p class="text-lg font-semibold tabular-nums">{{ number_format($log->morning_km, 2) }}</p>
    @if($log->morningRecordedTime())<p class="text-xs text-gray-400 mt-1">{{ $log->morningRecordedTime() }}</p>@endif
</div>
<form method="POST" action="{{ route('admin.tms.odometer.evening.store', $log) }}" class="space-y-4">
@csrf
<div><label class="erp-label">Evening KM</label><input type="number" step="0.01" min="{{ $log->morning_km }}" name="evening_km" class="erp-input" value="{{ old('evening_km') }}" required autofocus placeholder="Min {{ number_format($log->morning_km, 2) }}"></div>
<div><label class="erp-label">Notes</label><textarea name="notes" class="erp-input" rows="2">{{ old('notes', $log->notes) }}</textarea></div>
<div class="flex gap-2 pt-2">
<button type="submit" class="erp-btn-primary">Save Evening KM</button>
</div>
</form>
</div>
@endsection
