@extends('layouts.rental')
@section('title', 'Daily KM Log')
@section('page-title', 'Daily KM Log')
@section('page-subtitle', $vehicle->displayLabel())
@section('back', route('rental.dashboard'))
@section('content')
<div class="space-y-4">
<div class="flex items-center justify-between gap-2">
<p class="text-xs text-gray-500">Record morning and evening KM separately</p>
<a href="{{ route('rental.odometer.morning.create') }}" class="emp-btn-sm shrink-0">Morning KM</a>
</div>

<form method="GET" class="emp-card p-3 grid grid-cols-2 gap-2 items-end">
<div><label class="text-xs text-gray-500">From</label><input type="date" name="from" class="emp-input w-full mt-1" value="{{ $filters['from'] ?? '' }}"></div>
<div><label class="text-xs text-gray-500">To</label><input type="date" name="to" class="emp-input w-full mt-1" value="{{ $filters['to'] ?? '' }}"></div>
<div class="col-span-2"><button type="submit" class="emp-btn-secondary w-full">Filter</button></div>
</form>

<div class="emp-card overflow-hidden">
<div class="overflow-x-auto">
<table class="w-full text-sm min-w-[320px]">
<thead class="bg-gray-50 text-xs text-gray-500 uppercase">
<tr>
<th class="text-left p-3">Date</th>
<th class="text-right p-3">Morning</th>
<th class="text-right p-3">Evening</th>
<th class="text-right p-3">Daily</th>
<th class="text-center p-3">Status</th>
<th class="text-right p-3"></th>
</tr>
</thead>
<tbody>
@forelse($logs as $log)
<tr class="border-t border-gray-100">
<td class="p-3 text-xs whitespace-nowrap">{{ $log->log_date?->format('d M Y') }}</td>
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
<td class="p-3 text-center"><span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $log->statusBadgeClass() }}">{{ $log->statusLabel() }}</span></td>
<td class="p-3 text-right">
@if($log->needsEvening())
<a href="{{ route('rental.odometer.evening.create', $log) }}" class="emp-btn-sm">Evening KM</a>
@endif
</td>
</tr>
@empty
<tr><td colspan="6" class="p-6 text-center text-gray-400 text-sm">No odometer logs yet.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if($logs->hasPages())<div class="p-3 border-t">{{ $logs->links() }}</div>@endif
</div>
</div>
@endsection
