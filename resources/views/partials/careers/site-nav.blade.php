@php
    $siteNav = config('portal.careers_nav', []);
@endphp

<div class="careers-header-nav-wrap">
    <button type="button" class="careers-site-nav-toggle" aria-expanded="false" aria-controls="careers-site-nav-panel" aria-label="Toggle site menu">
        <svg class="careers-site-nav-toggle-icon careers-site-nav-toggle-open" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/>
        </svg>
        <svg class="careers-site-nav-toggle-icon careers-site-nav-toggle-close" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
        </svg>
    </button>

    <div id="careers-site-nav-panel" class="careers-header-nav-panel">
        <nav class="careers-site-nav" aria-label="Norban Group">
            @foreach($siteNav as $item)
                @if(! empty($item['children']))
                    <div class="careers-site-nav-dropdown">
                        <button type="button" class="careers-site-nav-trigger" aria-expanded="false">
                            {{ $item['label'] }}
                            <svg class="careers-site-nav-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="careers-site-nav-menu">
                            @foreach($item['children'] as $child)
                                <a href="{{ $child['url'] }}" target="_blank" rel="noopener">{{ $child['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ $item['url'] }}"
                       @if($item['external'] ?? true) target="_blank" rel="noopener" @endif
                       class="{{ ! empty($item['highlight']) ? 'careers-site-nav-cta' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>

        <nav class="careers-nav careers-nav-local" aria-label="Portal shortcuts">
            <a href="{{ route('careers.index') }}" class="{{ request()->routeIs('careers.index', 'careers.show', 'careers.apply') ? 'active' : '' }}">
                <svg class="careers-nav-icon" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Open Jobs
            </a>
            <a href="{{ route('careers.track') }}" class="{{ request()->routeIs('careers.track*') ? 'active' : '' }}">
                <svg class="careers-nav-icon" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Track Application
            </a>
        </nav>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var wrap = document.querySelector('.careers-header-nav-wrap');
    if (! wrap) return;

    var toggle = wrap.querySelector('.careers-site-nav-toggle');

    function closeDropdowns() {
        wrap.querySelectorAll('.careers-site-nav-dropdown.is-open').forEach(function (dropdown) {
            dropdown.classList.remove('is-open');
            dropdown.querySelector('.careers-site-nav-trigger')?.setAttribute('aria-expanded', 'false');
        });
    }

    toggle?.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = wrap.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (! open) {
            closeDropdowns();
        }
    });

    wrap.querySelectorAll('.careers-site-nav-dropdown').forEach(function (dropdown) {
        var trigger = dropdown.querySelector('.careers-site-nav-trigger');

        trigger?.addEventListener('click', function (e) {
            e.stopPropagation();
            var wasOpen = dropdown.classList.contains('is-open');
            closeDropdowns();
            if (! wasOpen) {
                dropdown.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
            }
        });
    });

    wrap.querySelectorAll('.careers-site-nav-menu a').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    document.addEventListener('click', function (e) {
        if (! wrap.contains(e.target)) {
            wrap.classList.remove('is-open');
            toggle?.setAttribute('aria-expanded', 'false');
            closeDropdowns();
        }
    });
})();
</script>
@endpush
