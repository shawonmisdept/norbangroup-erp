@php
    $title = $title ?? '';
    $subtitle = $subtitle ?? null;
@endphp

<div class="flex flex-col gap-3 mb-4 sm:mb-5 sm:flex-row sm:items-start sm:justify-between">
    <div class="min-w-0 flex-1">
        <h1 class="text-base sm:text-lg font-bold text-gray-900 leading-snug">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto sm:shrink-0 sm:justify-end">{!! $actions !!}</div>
    @endisset
</div>
