@extends('layouts.employee')
@section('title', 'Daily KM')
@section('content')
<div class="space-y-4">
<div class="flex items-center justify-between">
<h1 class="text-lg font-bold">Daily KM Log</h1>
<a href="{{ route('employee.transport.index') }}" class="emp-btn-secondary">← Back</a>
</div>
<p class="text-xs text-gray-500">{{ $vehicle->displayLabel() }} · Record morning and evening KM separately</p>

@if(!$todayLog || !$todayLog->hasMorning())
<div class="emp-card p-4 space-y-3">
<p class="text-sm font-semibold">Record Morning KM</p>
<form method="POST" action="{{ route('employee.transport.odometer.morning') }}" class="space-y-2">@csrf
<input type="number" step="0.01" min="0" name="morning_km" class="emp-input w-full" required placeholder="Morning KM" autofocus>
<button type="submit" class="emp-btn w-full">Save Morning KM</button>
</form>
</div>
@elseif($todayLog->needsEvening())
<div class="emp-card p-4 space-y-3">
<p class="text-sm font-semibold">Record Evening KM</p>
<p class="text-xs text-gray-500">Morning: {{ number_format($todayLog->morning_km, 2) }}@if($todayLog->morningRecordedTime()) · {{ $todayLog->morningRecordedTime() }}@endif</p>
<form method="POST" action="{{ route('employee.transport.odometer.evening', $todayLog) }}" class="space-y-2">@csrf
<input type="number" step="0.01" min="{{ $todayLog->morning_km }}" name="evening_km" class="emp-input w-full" required placeholder="Evening KM" autofocus>
<button type="submit" class="emp-btn w-full">Save Evening KM</button>
</form>
</div>
@else
<div class="emp-card p-4 text-sm">
<p class="font-semibold text-green-700">Today complete</p>
<p class="text-xs text-gray-500 mt-1">Morning {{ number_format($todayLog->morning_km, 2) }}@if($todayLog->morningRecordedTime()) · {{ $todayLog->morningRecordedTime() }}@endif · Evening {{ number_format($todayLog->evening_km, 2) }}@if($todayLog->eveningRecordedTime()) · {{ $todayLog->eveningRecordedTime() }}@endif</p>
</div>
@endif

<div class="emp-card overflow-hidden">
<table class="w-full text-sm">
<thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr><th class="text-left p-3">Date</th><th class="text-right p-3">Morning</th><th class="text-right p-3">Evening</th><th class="text-right p-3">Daily</th></tr></thead>
<tbody>
@forelse($logs as $log)
<tr class="border-t border-gray-100">
<td class="p-3 text-xs">{{ $log->log_date?->format('d M Y') }}</td>
<td class="p-3 text-right tabular-nums">
    @if($log->hasMorning())
        {{ number_format($log->morning_km, 2) }}
        @if($log->morningRecordedTime())<p class="text-[10px] text-gray-400">{{ $log->morningRecordedTime() }}</p>@endif
    @else — @endif
</td>
<td class="p-3 text-right tabular-nums">
    @if($log->hasEvening())
        {{ number_format($log->evening_km, 2) }}
        @if($log->eveningRecordedTime())<p class="text-[10px] text-gray-400">{{ $log->eveningRecordedTime() }}</p>@endif
    @else — @endif
</td>
<td class="p-3 text-right tabular-nums font-medium">{{ $log->dailyKm() !== null ? number_format($log->dailyKm(), 2) : '—' }}</td>
</tr>
@empty
<tr><td colspan="4" class="p-6 text-center text-gray-400 text-sm">No logs yet.</td></tr>
@endforelse
</tbody>
</table>
@if($logs->hasPages())<div class="p-3 border-t">{{ $logs->links() }}</div>@endif
</div>
</div>
@endsection
