@php
    $stroke = $active ?? false ? 'currentColor' : 'currentColor';
    $class = 'h-5 w-5';
@endphp

@switch($icon)
    @case('home')
        <svg class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="{{ $stroke }}" stroke-width="{{ ($active ?? false) ? 2.2 : 1.8 }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5L12 3l9 7.5M5 9.5V20a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V9.5"/>
        </svg>
        @break
    @case('clock')
        <svg class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="{{ $stroke }}" stroke-width="{{ ($active ?? false) ? 2.2 : 1.8 }}">
            <circle cx="12" cy="12" r="8.5"/>
            <path stroke-linecap="round" d="M12 8v4l2.5 2"/>
        </svg>
        @break
    @case('calendar')
        <svg class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="{{ $stroke }}" stroke-width="{{ ($active ?? false) ? 2.2 : 1.8 }}">
            <rect x="4" y="5" width="16" height="15" rx="2"/>
            <path stroke-linecap="round" d="M8 3v3M16 3v3M4 10h16"/>
        </svg>
        @break
    @case('wallet')
        <svg class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="{{ $stroke }}" stroke-width="{{ ($active ?? false) ? 2.2 : 1.8 }}">
            <rect x="3" y="7" width="18" height="12" rx="2"/>
            <path stroke-linecap="round" d="M3 11h18M16 15h2"/>
            <path stroke-linecap="round" d="M7 7V6a2 2 0 012-2h6a2 2 0 012 2v1"/>
        </svg>
        @break
    @case('user')
        <svg class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="{{ $stroke }}" stroke-width="{{ ($active ?? false) ? 2.2 : 1.8 }}">
            <circle cx="12" cy="8" r="3.5"/>
            <path stroke-linecap="round" d="M5 20c0-3.5 3-6 7-6s7 2.5 7 6"/>
        </svg>
        @break
    @case('late')
        <svg class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="{{ $stroke }}" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2"/>
            <circle cx="12" cy="12" r="8.5"/>
        </svg>
        @break
    @case('chevron-left')
        <svg class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/>
        </svg>
        @break
    @case('logout')
        <svg class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
        </svg>
        @break
    @case('star')
        <svg class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="{{ $stroke }}" stroke-width="{{ ($active ?? false) ? 2.2 : 1.8 }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l2.4 4.9 5.4.8-3.9 3.8.9 5.3L12 15.8l-4.8 2.5.9-5.3-3.9-3.8 5.4-.8L12 3z"/>
        </svg>
        @break
@endswitch
