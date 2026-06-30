@props(['driver' => null, 'size' => '180', 'round' => false, 'preview' => null, 'tone' => 'default'])

@php
    $sizeClass = match ($size) {
        '32' => 'w-8 h-8 text-xs',
        '40' => 'w-10 h-10 text-xs',
        '48' => 'w-12 h-12 text-sm',
        '56' => 'w-14 h-14 text-base',
        '80' => 'w-20 h-20 text-lg',
        '96' => 'w-24 h-24 text-xl',
        '180' => 'w-[180px] h-[180px] text-3xl',
        default => 'w-[180px] h-[180px] text-3xl',
    };
    $radiusClass = $round ? 'rounded-full' : 'rounded-sm';
    $fallbackClass = $tone === 'hero'
        ? 'bg-white/15 text-white border-white/20'
        : 'bg-orange-600 text-white border-gray-200';
@endphp

@if($preview)
    <img src="{{ $preview }}" alt="Preview"
         class="{{ $sizeClass }} object-cover {{ $radiusClass }} border shrink-0 {{ $tone === 'hero' ? 'border-white/20' : 'border-gray-200' }}">
@elseif($driver && $driver->photoUrl())
    <img src="{{ $driver->photoUrl() }}" alt="{{ $driver->name }}"
         class="{{ $sizeClass }} object-cover {{ $radiusClass }} border shrink-0 {{ $tone === 'hero' ? 'border-white/20' : 'border-gray-200' }}">
@elseif($driver)
    <div class="{{ $sizeClass }} flex items-center justify-center {{ $radiusClass }} border font-semibold shrink-0 {{ $fallbackClass }}">
        {{ $driver->initials() }}
    </div>
@else
    <div class="{{ $sizeClass }} flex items-center justify-center {{ $radiusClass }} border border-dashed border-gray-300 bg-gray-100 text-gray-400 font-semibold shrink-0">
        ?
    </div>
@endif
