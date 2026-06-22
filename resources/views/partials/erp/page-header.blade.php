@php
    $title = $title ?? '';
    $subtitle = $subtitle ?? null;
@endphp

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-gray-900">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-xs text-gray-500 mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2 shrink-0">{!! $actions !!}</div>
    @endisset
</div>
