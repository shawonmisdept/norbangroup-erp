@props(['user', 'size' => '180'])

@php
    $sizeClass = match ($size) {
        '32' => 'w-8 h-8 text-xs',
        '40' => 'w-10 h-10 text-xs',
        '180' => 'w-[180px] h-[180px] text-3xl',
        default => 'w-[180px] h-[180px] text-3xl',
    };
@endphp

@if($user->photoUrl())
    <img src="{{ $user->photoUrl() }}" alt="{{ $user->name }}"
         class="{{ $sizeClass }} object-cover rounded-sm border border-gray-200 shrink-0">
@else
    <div class="{{ $sizeClass }} flex items-center justify-center rounded-sm border border-gray-200 bg-brand text-white font-semibold shrink-0">
        {{ $user->initials() }}
    </div>
@endif
