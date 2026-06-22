@php
    $masterGroups = config('masters.groups');
    $activeModule = request()->route('module');
    $activeMasterGroup = null;

    if ($activeModule) {
        foreach ($masterGroups as $groupName => $modules) {
            if (in_array($activeModule, $modules, true)) {
                $activeMasterGroup = $groupName;
                break;
            }
        }
    }

    $groupIcons = [
        'Commercial'          => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        'Organization'        => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'Product'             => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        'Material & Fabric'   => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
        'Order & Shipment'    => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
        'Production & Status' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'Finance'             => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'Supplier'            => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'Sample'              => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
    ];
@endphp

<aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-erp-border shadow-sm flex flex-col transform transition-transform duration-200 lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

    {{-- Brand header --}}
    <div class="h-14 flex items-center gap-3 px-4 bg-brand text-white shrink-0">
        @if(config('portal.navbar_logo'))
            <img src="{{ config('portal.navbar_logo') }}" alt="{{ config('portal.name') }}"
                 class="h-8 w-auto max-w-[120px] object-contain shrink-0">
        @else
            <div class="w-8 h-8 bg-gold rounded-sm flex items-center justify-center shrink-0 shadow-sm">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M4 7h16M4 12h16M4 17h10" stroke-linecap="round"/>
                </svg>
            </div>
        @endif
        <div class="min-w-0 flex-1">
            <p class="text-sm font-bold truncate leading-tight">{{ config('portal.name') }}</p>
            <p class="text-[10px] text-white/50 uppercase tracking-widest">{{ config('portal.tagline', 'Commercial ERP') }}</p>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto erp-sidebar-scroll py-2 px-2">

        <p class="erp-nav-section">Operations</p>

        @if(auth()->user()->hasPermission('orders.view'))
            <a href="{{ route('admin.requirements.index') }}"
               class="erp-nav-link {{ request()->routeIs('admin.requirements.*') ? 'erp-nav-link-active' : '' }}">
                <svg class="w-4 h-4 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Requirements
            </a>
        @endif

        @if(auth()->user()->hasAnyMasterViewPermission())
            <div class="flex items-center justify-between px-3 pt-4 pb-1">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Master Data</p>
                <a href="{{ route('admin.masters.hub') }}"
                   class="text-[10px] font-semibold text-brand hover:text-brand-dark uppercase tracking-wide">
                    Hub
                </a>
            </div>

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
                <div class="{{ $isCommercial ? 'erp-nav-commercial' : '' }}">
                    <button type="button"
                            @click="openGroups['{{ $groupKey }}'] = !openGroups['{{ $groupKey }}']"
                            class="erp-nav-group {{ $isGroupActive ? 'erp-nav-group-active' : '' }}">
                        <span class="erp-nav-group-icon {{ $isCommercial ? '' : 'bg-brand/10 text-brand' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path d="{{ $groupIcons[$groupName] ?? 'M4 6h16M4 10h16M4 14h16M4 18h16' }}" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span class="flex-1 text-left font-medium">{{ $groupName }}</span>
                        <span class="text-[10px] tabular-nums text-gray-400 mr-0.5">{{ count($visibleModules) }}</span>
                        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform shrink-0"
                             :class="openGroups['{{ $groupKey }}'] && 'rotate-180'"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <div x-show="openGroups['{{ $groupKey }}']" x-cloak class="erp-nav-sub">
                        @foreach($visibleModules as $moduleKey)
                            @php $mod = config("masters.modules.{$moduleKey}"); @endphp
                            @if($mod)
                                <a href="{{ route('admin.masters.index', $moduleKey) }}"
                                   class="erp-nav-sub-link {{ request()->route('module') === $moduleKey ? 'erp-nav-sub-link-active' : '' }}">
                                    {{ $mod['label_plural'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif

        @if(auth()->user()->hasPermission('users.manage') || auth()->user()->hasPermission('roles.manage') || auth()->user()->hasPermission('settings.manage'))
            <p class="erp-nav-section">Administration</p>

            <button type="button" @click="adminOpen = !adminOpen"
                    class="erp-nav-group w-full">
                <span class="erp-nav-group-icon bg-brand/10 text-brand">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="flex-1 text-left font-medium">System Admin</span>
                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform shrink-0"
                     :class="adminOpen && 'rotate-180'"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div x-show="adminOpen" x-cloak class="erp-nav-sub">
                @if(auth()->user()->hasPermission('users.manage'))
                    <a href="{{ route('admin.users.index') }}"
                       class="erp-nav-sub-link {{ request()->routeIs('admin.users.*') ? 'erp-nav-sub-link-active' : '' }}">
                        Users
                    </a>
                @endif
                @if(auth()->user()->hasPermission('roles.manage'))
                    <a href="{{ route('admin.roles.index') }}"
                       class="erp-nav-sub-link {{ request()->routeIs('admin.roles.*') ? 'erp-nav-sub-link-active' : '' }}">
                        Roles & Permissions
                    </a>
                @endif
                @if(auth()->user()->hasPermission('settings.manage'))
                    <a href="{{ route('admin.settings.edit') }}"
                       class="erp-nav-sub-link {{ request()->routeIs('admin.settings.*') ? 'erp-nav-sub-link-active' : '' }}">
                        App Settings
                    </a>
                @endif
                <a href="{{ route('admin.profile.edit') }}"
                   class="erp-nav-sub-link {{ request()->routeIs('admin.profile.*') ? 'erp-nav-sub-link-active' : '' }}">
                    My Profile
                </a>
            </div>
        @elseif(auth()->check())
            <p class="erp-nav-section">Account</p>
            <a href="{{ route('admin.profile.edit') }}"
               class="erp-nav-link {{ request()->routeIs('admin.profile.*') ? 'erp-nav-link-active' : '' }}">
                My Profile
            </a>
        @endif
    </nav>

    {{-- Footer --}}
    <div class="p-3 border-t border-erp-border bg-gray-50/80 shrink-0">
        <a href="{{ route('orders.create') }}" target="_blank"
           class="flex items-center gap-2 px-3 py-2 text-xs text-gray-500 hover:text-brand hover:bg-white rounded-sm border border-transparent hover:border-erp-border transition">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Public Requirement Form
        </a>
    </div>
</aside>
