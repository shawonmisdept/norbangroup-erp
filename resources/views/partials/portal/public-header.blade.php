@php
    $brandLabel = $brandLabel ?? 'Portal';
    $brandUrl = $brandUrl ?? url('/');
@endphp

<header class="careers-header">
    <div class="careers-header-accent" aria-hidden="true"></div>
    <div class="careers-header-inner">
        <div class="careers-header-top">
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
            <button type="button" class="careers-site-nav-toggle" aria-expanded="false" aria-controls="careers-site-nav-panel" aria-label="Toggle site menu">
                <svg class="careers-site-nav-toggle-icon careers-site-nav-toggle-open" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/>
                </svg>
                <svg class="careers-site-nav-toggle-icon careers-site-nav-toggle-close" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
        @include('partials.careers.site-nav')
    </div>
</header>
