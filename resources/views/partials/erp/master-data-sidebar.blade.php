@php
    $hasErpMasters = auth()->user()->hasAnyMasterViewPermission();
    $hasHrmMasters = auth()->user()->hasAnyHrmMasterViewPermission();
    $masterDataActive = request()->routeIs('admin.masters.*', 'admin.hrm.masters.*');
@endphp

<div class="erp-nav-board" data-nav-branch>
    <button type="button"
            @click="openGroups['master_data'] = !openGroups['master_data']"
            data-nav-label="Core Modules Master Data"
            class="erp-nav-group w-full {{ $masterDataActive ? 'erp-nav-group-active' : '' }}">
        <span class="erp-nav-group-icon erp-nav-group-icon-master">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7c-2 0-3 1-3 3zM9 11h6M9 15h4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
        <span class="flex-1 text-left font-medium">Master Data</span>
        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform shrink-0"
             :class="openGroups['master_data'] && 'rotate-180'"
             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>

    <div x-show="openGroups['master_data']" x-cloak class="erp-nav-sub" data-nav-open-key="master_data">
        @if($hasErpMasters)
            <a href="{{ route('admin.masters.hub') }}"
               data-nav-label="Core Modules ERP Hub"
               class="erp-nav-sub-link {{ request()->routeIs('admin.masters.hub') ? 'erp-nav-sub-link-active' : '' }}">
                ERP Hub
            </a>
        @endif
        @if($hasHrmMasters)
            <a href="{{ route('admin.hrm.masters.hub') }}"
               data-nav-label="Core Modules HRM Hub"
               class="erp-nav-sub-link {{ request()->routeIs('admin.hrm.masters.hub') ? 'erp-nav-sub-link-active' : '' }}">
                HRM Hub
            </a>
        @endif

        @if($hasErpMasters)
            @foreach($masterGroups as $groupName => $modules)
                @php
                    $visibleModules = array_values(array_filter(
                        $modules,
                        fn (string $moduleKey) => auth()->user()->canViewMaster($moduleKey)
                    ));
                @endphp
                @continue($visibleModules === [])
                @php
                    $groupKey = str_replace([' ', '&'], ['_', 'and'], $groupName);
                    $isGroupActive = $activeMasterGroup === $groupName;
                    $isCommercial = $groupName === 'Commercial';
                @endphp
                <div class="{{ $isCommercial ? 'erp-nav-commercial' : '' }}" data-nav-branch>
                    <button type="button"
                            @click="openGroups['{{ $groupKey }}'] = !openGroups['{{ $groupKey }}']"
                            data-nav-label="Core Modules Master Data {{ $groupName }}"
                            class="erp-nav-sub-group {{ $isGroupActive ? 'erp-nav-sub-group-active' : '' }}">
                        <span class="flex-1 text-left">{{ $groupName }}</span>
                        <span class="text-[10px] tabular-nums text-gray-400 mr-1">{{ count($visibleModules) }}</span>
                        <svg class="w-3 h-3 text-gray-400 transition-transform shrink-0"
                             :class="openGroups['{{ $groupKey }}'] && 'rotate-180'"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div x-show="openGroups['{{ $groupKey }}']" x-cloak class="erp-nav-sub-nested" data-nav-open-key="{{ $groupKey }}">
                        @foreach($visibleModules as $moduleKey)
                            @php $mod = config("masters.modules.{$moduleKey}"); @endphp
                            @if($mod)
                                <a href="{{ route('admin.masters.index', $moduleKey) }}"
                                   data-nav-label="Core Modules Master Data {{ $groupName }} {{ $mod['label_plural'] }}"
                                   class="erp-nav-sub-link {{ request()->route('module') === $moduleKey && request()->routeIs('admin.masters.*') ? 'erp-nav-sub-link-active' : '' }}">
                                    {{ $mod['label_plural'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif

        @if($hasHrmMasters)
            @foreach($hrmGroups as $groupName => $modules)
                @php
                    $visibleHrmModules = array_values(array_filter(
                        $modules,
                        fn (string $moduleKey) => auth()->user()->canViewHrmMaster($moduleKey)
                    ));
                @endphp
                @continue($visibleHrmModules === [])
                @php
                    $hrmGroupKey = 'hrm_' . str_replace([' ', '&'], ['_', 'and'], $groupName);
                    $isHrmGroupActive = $activeHrmGroup === $groupName;
                @endphp
                <div data-nav-branch>
                    <button type="button"
                            @click="openGroups['{{ $hrmGroupKey }}'] = !openGroups['{{ $hrmGroupKey }}']"
                            data-nav-label="Core Modules Master Data HRM {{ $groupName }}"
                            class="erp-nav-sub-group {{ $isHrmGroupActive ? 'erp-nav-sub-group-active' : '' }}">
                        <span class="flex-1 text-left">{{ $groupName }}</span>
                        <span class="text-[10px] tabular-nums text-gray-400 mr-1">{{ count($visibleHrmModules) }}</span>
                        <svg class="w-3 h-3 text-gray-400 transition-transform shrink-0"
                             :class="openGroups['{{ $hrmGroupKey }}'] && 'rotate-180'"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div x-show="openGroups['{{ $hrmGroupKey }}']" x-cloak class="erp-nav-sub-nested" data-nav-open-key="{{ $hrmGroupKey }}">
                        @foreach($visibleHrmModules as $moduleKey)
                            @php $mod = config("hrm.modules.{$moduleKey}"); @endphp
                            @if($mod)
                                <a href="{{ route('admin.hrm.masters.index', $moduleKey) }}"
                                   data-nav-label="Core Modules Master Data HRM {{ $groupName }} {{ $mod['label_plural'] }}"
                                   class="erp-nav-sub-link {{ request()->route('module') === $moduleKey && request()->routeIs('admin.hrm.masters.*') ? 'erp-nav-sub-link-active' : '' }}">
                                    {{ $mod['label_plural'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
