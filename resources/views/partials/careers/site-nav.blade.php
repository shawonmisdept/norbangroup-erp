@php
    $siteNav = config('portal.careers_nav', []);
@endphp

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
                            @php
                                $childUrl = ! empty($child['route']) ? route($child['route']) : ($child['url'] ?? '#');
                            @endphp
                            <a href="{{ $childUrl }}" @if(! empty($child['external'])) target="_blank" rel="noopener" @endif>{{ $child['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            @else
                @php
                    $itemUrl = ! empty($item['route']) ? route($item['route']) : ($item['url'] ?? '#');
                @endphp
                <a href="{{ $itemUrl }}"
                   @if(! empty($item['external'])) target="_blank" rel="noopener" @endif
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
    </nav>
</div>

@push('scripts')
<script>
(function () {
    var header = document.querySelector('.careers-header-inner');
    if (! header) return;

    var toggle = header.querySelector('.careers-site-nav-toggle');
    var panel = header.querySelector('.careers-header-nav-panel');

    function closeDropdowns() {
        header.querySelectorAll('.careers-site-nav-dropdown.is-open').forEach(function (dropdown) {
            dropdown.classList.remove('is-open');
            dropdown.querySelector('.careers-site-nav-trigger')?.setAttribute('aria-expanded', 'false');
        });
    }

    toggle?.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = header.classList.toggle('is-nav-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (! open) {
            closeDropdowns();
        }
    });

    header.querySelectorAll('.careers-site-nav-dropdown').forEach(function (dropdown) {
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

    panel?.querySelectorAll('.careers-site-nav-menu a').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    document.addEventListener('click', function (e) {
        if (! header.contains(e.target)) {
            header.classList.remove('is-nav-open');
            toggle?.setAttribute('aria-expanded', 'false');
            closeDropdowns();
        }
    });
})();
</script>
@endpush
