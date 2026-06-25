@props([
    'section',
    'label',
    'icon',
    'openKey',
    'canViewAny',
])

@php
    $submodules = config("hrm.{$section}_submodules", []);
    $navGroups = config("hrm.{$section}_nav_groups", []);
    $hubRoute = "admin.hrm.{$section}.hub";
    $canView = match ($section) {
        'employee' => fn ($key) => auth()->user()->canViewEmployeeSubmodule($key),
        'recruitment' => fn ($key) => auth()->user()->canViewRecruitmentSubmodule($key),
        'leave' => fn ($key) => auth()->user()->canViewLeaveSubmodule($key),
        'attendance' => fn ($key) => auth()->user()->canViewAttendanceSubmodule($key),
        'compliance' => fn ($key) => auth()->user()->canViewComplianceSubmodule($key),
        'finance' => fn ($key) => auth()->user()->canViewFinanceSubmodule($key),
        'rmg' => fn ($key) => auth()->user()->canViewRmgSubmodule($key),
        default => fn ($key) => auth()->user()->canViewSalarySubmodule($key),
    };
    $isActive = match ($section) {
        'employee' => request()->routeIs(
            'admin.hrm.employee.*',
            'admin.hrm.employees.*',
            'admin.hrm.separations.*',
            'admin.hrm.promotions.*',
            'admin.hrm.letters.*',
            'admin.hrm.discipline.*',
        ),
        default => request()->routeIs("admin.hrm.{$section}.*"),
    };
    $routeMatch = function (string $key) use ($section, $submodules) {
        if (! isset($submodules[$key]['route'])) {
            return false;
        }

        $base = preg_replace('/\.(index|hub)$/', '', $submodules[$key]['route']);

        return request()->routeIs($base) || request()->routeIs($base . '.*');
    };
    $groupKey = fn (string $groupLabel) => $openKey . '_grp_' . str_replace([' ', '&'], ['_', 'and'], $groupLabel);
@endphp

@if($canViewAny)
    <div data-nav-branch>
        <button type="button"
                @click="openGroups['{{ $openKey }}'] = !openGroups['{{ $openKey }}']"
                data-nav-label="HRM {{ $label }}"
                class="erp-nav-group {{ $isActive ? 'erp-nav-group-active' : '' }} w-full">
            <span class="erp-nav-group-icon erp-nav-group-icon-hrm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="{{ $icon }}" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="flex-1 text-left font-medium">{{ $label }}</span>
            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform shrink-0"
                 :class="openGroups['{{ $openKey }}'] && 'rotate-180'"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>

        <div x-show="openGroups['{{ $openKey }}']" x-cloak class="erp-nav-sub" data-nav-open-key="{{ $openKey }}">
            <a href="{{ route($hubRoute) }}"
               data-nav-label="HRM {{ $label }} Hub"
               class="erp-nav-sub-link {{ request()->routeIs($hubRoute) ? 'erp-nav-sub-link-active' : '' }}">
                Hub
            </a>

            @if($navGroups !== [])
                @foreach($navGroups as $groupLabel => $keys)
                    @php
                        $visibleKeys = array_values(array_filter(
                            $keys,
                            fn ($key) => isset($submodules[$key])
                                && ($submodules[$key]['status'] ?? '') === 'active'
                                && $canView($key)
                        ));
                        $gKey = $groupKey($groupLabel);
                        $isGroupActive = collect($visibleKeys)->contains(fn ($key) => $routeMatch($key));
                    @endphp
                    @continue($visibleKeys === [])
                    <div data-nav-branch>
                    <button type="button"
                            @click="openGroups['{{ $gKey }}'] = !openGroups['{{ $gKey }}']"
                            data-nav-label="HRM {{ $label }} {{ $groupLabel }}"
                            class="erp-nav-sub-group {{ $isGroupActive ? 'erp-nav-sub-group-active' : '' }}">
                        <span class="flex-1 text-left">{{ $groupLabel }}</span>
                        <span class="text-[10px] tabular-nums text-gray-400 mr-1">{{ count($visibleKeys) }}</span>
                        <svg class="w-3 h-3 text-gray-400 transition-transform shrink-0"
                             :class="openGroups['{{ $gKey }}'] && 'rotate-180'"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div x-show="openGroups['{{ $gKey }}']" x-cloak class="erp-nav-sub-nested" data-nav-open-key="{{ $gKey }}">
                        @foreach($visibleKeys as $key)
                            @php $sub = $submodules[$key]; @endphp
                            <a href="{{ route($sub['route']) }}"
                               data-nav-label="HRM {{ $label }} {{ $groupLabel }} {{ $sub['label'] }}"
                               class="erp-nav-sub-link {{ $routeMatch($key) ? 'erp-nav-sub-link-active' : '' }}">
                                {{ $sub['label'] }}
                            </a>
                        @endforeach
                    </div>
                    </div>
                @endforeach
            @else
                @foreach($submodules as $key => $sub)
                    @if(($sub['status'] ?? '') === 'active' && $canView($key))
                        <a href="{{ route($sub['route']) }}"
                           data-nav-label="HRM {{ $label }} {{ $sub['label'] }}"
                           class="erp-nav-sub-link {{ $routeMatch($key) ? 'erp-nav-sub-link-active' : '' }}">
                            {{ $sub['label'] }}
                        </a>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
@endif
