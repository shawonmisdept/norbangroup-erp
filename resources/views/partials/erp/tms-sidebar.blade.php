@php
    $submodules = config('tms.submodules', []);
    $navGroups = config('tms.nav_groups', []);
    $canView = fn (string $key) => auth()->user()->canViewTmsSubmodule($key);
    $isActive = request()->routeIs('admin.tms.*');
    $routeMatch = function (string $key) use ($submodules) {
        if (! isset($submodules[$key]['route'])) {
            return false;
        }

        $base = preg_replace('/\.(index|hub|dashboard)$/', '', $submodules[$key]['route']);

        if (! request()->routeIs($base) && ! request()->routeIs($base . '.*')) {
            return false;
        }

        foreach ($submodules[$key]['active_excludes'] ?? [] as $pattern) {
            if (request()->routeIs($pattern)) {
                return false;
            }
        }

        return true;
    };
    $groupKey = fn (string $groupLabel) => 'tms_grp_' . str_replace([' ', '&'], ['_', 'and'], $groupLabel);
    $visibleKeys = array_values(array_filter(
        array_keys($submodules),
        fn ($key) => $key !== 'dashboard'
            && ($submodules[$key]['status'] ?? '') === 'active'
            && $canView($key)
    ));
@endphp

@if(auth()->user()->hasAnyTmsViewPermission())
    <p class="erp-nav-section" data-nav-section>Transport</p>

    <div data-nav-branch>
        <button type="button"
                @click="openGroups['tms'] = !openGroups['tms']"
                data-nav-label="Transport Management"
                class="erp-nav-group {{ $isActive ? 'erp-nav-group-active' : '' }} w-full">
            <span class="erp-nav-group-icon erp-nav-group-icon-hrm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="flex-1 text-left font-medium">Transport</span>
            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform shrink-0"
                 :class="openGroups['tms'] && 'rotate-180'"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>

        <div x-show="openGroups['tms']" x-cloak class="erp-nav-sub" data-nav-open-key="tms">
            @if(auth()->user()->hasAnyTmsViewPermission())
                <a href="{{ route('admin.tms.hub') }}"
                   data-nav-label="Transport Hub"
                   class="erp-nav-sub-link {{ request()->routeIs('admin.tms.hub') ? 'erp-nav-sub-link-active' : '' }}">
                    Hub
                </a>
            @endif

            @if(isset($submodules['dashboard']) && ($submodules['dashboard']['status'] ?? '') === 'active' && $canView('dashboard'))
                <a href="{{ route($submodules['dashboard']['route']) }}"
                   data-nav-label="Transport Dashboard"
                   class="erp-nav-sub-link {{ $routeMatch('dashboard') ? 'erp-nav-sub-link-active' : '' }}">
                    Dashboard
                </a>
            @endif

            @foreach($navGroups as $groupLabel => $keys)
                @php
                    $groupVisible = array_values(array_filter(
                        $keys,
                        fn ($key) => isset($submodules[$key])
                            && ($submodules[$key]['status'] ?? '') === 'active'
                            && $canView($key)
                    ));
                    $gKey = $groupKey($groupLabel);
                    $isGroupActive = collect($groupVisible)->contains(fn ($key) => $routeMatch($key));
                @endphp
                @continue($groupVisible === [])
                <div data-nav-branch>
                    <button type="button"
                            @click="openGroups['{{ $gKey }}'] = !openGroups['{{ $gKey }}']"
                            data-nav-label="Transport {{ $groupLabel }}"
                            class="erp-nav-sub-group {{ $isGroupActive ? 'erp-nav-sub-group-active' : '' }}">
                        <span class="flex-1 text-left">{{ $groupLabel }}</span>
                        <span class="text-[10px] tabular-nums text-gray-400 mr-1">{{ count($groupVisible) }}</span>
                        <svg class="w-3 h-3 text-gray-400 transition-transform shrink-0"
                             :class="openGroups['{{ $gKey }}'] && 'rotate-180'"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div x-show="openGroups['{{ $gKey }}']" x-cloak class="erp-nav-sub-nested" data-nav-open-key="{{ $gKey }}">
                        @foreach($groupVisible as $key)
                            @php $sub = $submodules[$key]; @endphp
                            <a href="{{ route($sub['route']) }}"
                               data-nav-label="Transport {{ $groupLabel }} {{ $sub['label'] }}"
                               class="erp-nav-sub-link {{ $routeMatch($key) ? 'erp-nav-sub-link-active' : '' }}">
                                {{ $sub['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if(isset($submodules['reports']) && ($submodules['reports']['status'] ?? '') === 'active' && $canView('reports'))
                <a href="{{ route($submodules['reports']['route']) }}"
                   data-nav-label="Transport Reports"
                   class="erp-nav-sub-link {{ $routeMatch('reports') ? 'erp-nav-sub-link-active' : '' }}">
                    Reports
                </a>
            @endif
        </div>
    </div>
@endif
