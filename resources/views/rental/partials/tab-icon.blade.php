@php
    $stroke = $active ?? false ? 'currentColor' : 'currentColor';
    $class = 'h-5 w-5';
@endphp

@switch($icon)
    @case('car')
        <svg class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="{{ $stroke }}" stroke-width="{{ ($active ?? false) ? 2.2 : 1.8 }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 11l1.5-4h11L19 11M5 11v6a1 1 0 001 1h1a1 1 0 001-1v-1h8v1a1 1 0 001 1h1a1 1 0 001-1v-6M7 15h.01M17 15h.01"/>
            <circle cx="7.5" cy="15.5" r="1"/>
            <circle cx="16.5" cy="15.5" r="1"/>
        </svg>
        @break
    @case('gauge')
        <svg class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="{{ $stroke }}" stroke-width="{{ ($active ?? false) ? 2.2 : 1.8 }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l3-3"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4a8 8 0 108 8"/>
            <path stroke-linecap="round" d="M12 8v4"/>
        </svg>
        @break
    @case('bell')
        <svg class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="{{ $stroke }}" stroke-width="{{ ($active ?? false) ? 2.2 : 1.8 }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @break
    @default
        @include('employee.partials.tab-icon', ['icon' => $icon, 'active' => $active ?? false])
@endswitch
