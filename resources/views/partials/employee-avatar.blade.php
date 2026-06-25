@props(['employee' => null, 'size' => '180', 'round' => false, 'preview' => null])

@php
    $sizeClass = match ($size) {
        '32' => 'w-8 h-8 text-xs',
        '40' => 'w-10 h-10 text-xs',
        '48' => 'w-12 h-12 text-sm',
        '56' => 'w-14 h-14 text-base',
        '80' => 'w-20 h-20 text-lg',
        '180' => 'w-[180px] h-[180px] text-3xl',
        default => 'w-[180px] h-[180px] text-3xl',
    };
    $radiusClass = $round ? 'rounded-full' : 'rounded-sm';
@endphp

@if($preview)
    <img src="{{ $preview }}" alt="Preview"
         class="{{ $sizeClass }} object-cover {{ $radiusClass }} border border-gray-200 shrink-0">
@elseif($employee && $employee->photoUrl())
    <img src="{{ $employee->photoUrl() }}" alt="{{ $employee->name }}"
         class="{{ $sizeClass }} object-cover {{ $radiusClass }} border border-gray-200 shrink-0">
@elseif($employee)
    <div class="{{ $sizeClass }} flex items-center justify-center {{ $radiusClass }} border border-gray-200 bg-brand text-white font-semibold shrink-0">
        {{ $employee->initials() }}
    </div>
@else
    <div class="{{ $sizeClass }} flex items-center justify-center {{ $radiusClass }} border border-dashed border-gray-300 bg-gray-100 text-gray-400 font-semibold shrink-0">
        ?
    </div>
@endif
