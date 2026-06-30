@php
    $logoUrl = config('portal.frontend_logo') ?: config('portal.navbar_logo');
    $size = $size ?? 'md';
    $variant = $variant ?? 'employee';
    $invert = $invert ?? true;
    $showName = $showName ?? false;
    $subtitle = $subtitle ?? null;
    $centered = $centered ?? false;

    $boxClass = match ($size) {
        'sm' => 'h-9 w-9 p-1.5',
        'lg' => 'h-16 w-16 p-3',
        default => 'h-14 w-14 p-2.5',
    };

    $fallbackTextClass = match ($size) {
        'sm' => 'text-sm',
        'lg' => 'text-2xl',
        default => 'text-xl',
    };

    $shellClass = match ($variant) {
        'rental' => 'bg-orange-500/20 ring-1 ring-orange-400/30 backdrop-blur',
        default => 'bg-white/10 backdrop-blur',
    };
@endphp

<div @class(['portal-brand', 'text-center' => $centered, 'flex items-center gap-2.5' => ! $centered])>
    @if($logoUrl)
        <div @class([
            'portal-brand-box mx-auto flex shrink-0 items-center justify-center rounded-2xl',
            $boxClass,
            $shellClass,
            'mb-4' => $centered,
        ])>
            <img src="{{ $logoUrl }}"
                 alt="{{ config('portal.name') }}"
                 @class([
                     'h-full w-full object-contain',
                     'brightness-0 invert' => $invert,
                 ])>
        </div>
    @else
        <div @class([
            'portal-brand-box mx-auto flex shrink-0 items-center justify-center rounded-2xl font-bold text-white',
            $boxClass,
            $shellClass,
            $fallbackTextClass,
            'mb-4' => $centered,
        ])>
            {{ strtoupper(substr(config('portal.name'), 0, 1)) }}
        </div>
    @endif

    @if($showName || $subtitle)
        <div @class(['min-w-0', 'mt-0' => ! $centered])>
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
                ])>{{ $subtitle }}</p>
            @endif
        </div>
    @endif
</div>
