@php
    $logoUrl = config('portal.frontend_logo') ?: config('portal.navbar_logo');
    $size = $size ?? 'md';
    $variant = $variant ?? 'employee';
    $invert = $invert ?? true;
    $showName = $showName ?? false;
    $subtitle = $subtitle ?? null;
    $centered = $centered ?? false;

    $logoHeightClass = match ($size) {
        'sm' => 'h-8 max-w-[7rem]',
        'lg' => 'h-14 max-w-[12rem]',
        default => 'h-10 max-w-[9rem]',
    };

    $fallbackClass = match ($size) {
        'sm' => 'h-8 w-8 text-sm',
        'lg' => 'h-14 w-14 text-2xl',
        default => 'h-10 w-10 text-lg',
    };
@endphp

<div @class([
    'portal-brand',
    'text-center' => $centered,
    'flex items-center gap-2.5' => ! $centered,
    'flex-col items-center' => $centered,
])>
    @if($logoUrl)
        <img src="{{ $logoUrl }}"
             alt="{{ config('portal.name') }}"
             @class([
                 'portal-brand-logo shrink-0 object-contain object-left',
                 $logoHeightClass,
                 'mx-auto' => $centered,
                 'brightness-0 invert' => $invert,
             ])>
    @else
        <div @class([
            'portal-brand-fallback flex shrink-0 items-center justify-center font-bold text-white',
            $fallbackClass,
            'mx-auto' => $centered,
        ])>
            {{ strtoupper(substr(config('portal.name'), 0, 1)) }}
        </div>
    @endif

    @if($showName || $subtitle)
        <div @class(['min-w-0 text-left' => ! $centered])>
            @if($showName)
                <p @class([
                    'font-bold text-white leading-tight',
                    $centered ? 'text-xl' : ($size === 'sm' ? 'text-xs' : 'text-sm'),
                ])>{{ config('portal.name') }}</p>
            @endif
            @if($subtitle)
                <p @class([
                    'leading-tight',
                    $centered ? 'mt-1 text-sm text-white/60' : 'text-[10px] text-white/50',
                    $variant === 'rental' && $centered ? '!text-orange-200/70' : '',
                    $variant === 'rental' && ! $centered ? '!text-orange-200/60' : '',
                ])>{{ $subtitle }}</p>
            @endif
        </div>
    @endif
</div>
