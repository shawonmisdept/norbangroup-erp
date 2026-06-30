@php
    $brandLabel = $brandLabel ?? 'Portal';
    $brandUrl = $brandUrl ?? url('/');
@endphp

<header class="careers-header">
    <div class="careers-header-accent" aria-hidden="true"></div>
    <div class="careers-header-inner">
        <a href="{{ $brandUrl }}" class="careers-brand">
            <span class="careers-brand-logo">
                @if($logo = config('portal.frontend_logo') ?: config('portal.navbar_logo'))
                    <img src="{{ $logo }}" alt="Logo" class="careers-brand-logo-img">
                @else
                    <span class="careers-logo-mark">N</span>
                @endif
            </span>
            <span class="careers-brand-divider" aria-hidden="true"></span>
            <span class="careers-brand-label">{{ $brandLabel }}</span>
        </a>
        @include('partials.careers.site-nav')
    </div>
</header>
