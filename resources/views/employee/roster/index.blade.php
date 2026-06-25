@extends('layouts.employee')

@section('title', 'My Roster')
@section('page-title', 'My Roster')

@section('content')
<div class="space-y-4 pb-4">
    <p class="text-xs text-gray-500">{{ $start->format('d M') }} – {{ $end->format('d M Y') }}</p>

    <div class="emp-card overflow-hidden">
        @foreach($dates as $date)
            @php
                $entry = $entries->get($date);
                $day = \Carbon\Carbon::parse($date);
            @endphp
            <div class="flex items-center gap-3 p-4 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                <div class="flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-xl bg-gray-50 text-center">
                    <span class="text-[10px] font-bold leading-none text-gray-400">{{ $day->format('D') }}</span>
                    <span class="text-sm font-bold leading-none tabular-nums text-gray-800">{{ $day->format('d') }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">{{ $day->format('l, d M') }}</p>
                    @if($entry)
                        <p class="text-xs text-gray-600">{{ $entry->shift?->name ?? '—' }}</p>
                        @if($entry->line)
                            <p class="text-[11px] text-gray-400">Line: {{ $entry->line->name }}</p>
                        @endif
                    @else
                        <p class="text-xs text-gray-400">Default shift — {{ $employee->shift?->name ?? 'Not assigned' }}</p>
                    @endif
                </div>
                @if($entry?->shift)
                    <span class="emp-badge bg-brand/10 text-brand">{{ $entry->shift->code ?? 'SFT' }}</span>
                @endif
            </div>
        @endforeach
    </div>

    @if($entries->isEmpty())
        <p class="text-center text-sm text-gray-400">No published roster for this week — your default shift applies.</p>
    @endif
</div>
@endsection
