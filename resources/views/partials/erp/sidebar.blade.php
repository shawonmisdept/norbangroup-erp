@php
    $masterGroups = config('masters.groups');
    $hrmGroups = config('hrm.groups');
    $kbAccess = app(\App\Services\KbAccessService::class);
    $canViewKb = auth()->check() && $kbAccess->canViewKb(auth()->user());
    $activeModule = request()->route('module');
    $activeMasterGroup = null;
    $activeHrmGroup = null;

    if ($activeModule && request()->routeIs('admin.masters.*')) {
        foreach ($masterGroups as $groupName => $modules) {
            if (in_array($activeModule, $modules, true)) {
                $activeMasterGroup = $groupName;
                break;
            }
        }
    }

    if ($activeModule && request()->routeIs('admin.hrm.masters.*')) {
        foreach ($hrmGroups as $groupName => $modules) {
            if (in_array($activeModule, $modules, true)) {
                $activeHrmGroup = $groupName;
                break;
            }
        }
    }

@endphp

<aside class="erp-sidebar fixed inset-y-0 left-0 z-50 w-64 flex flex-col transform transition-transform duration-200 lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

    {{-- Brand header --}}
    <div class="erp-sidebar-brand h-14 flex items-center gap-3 px-4 shrink-0">
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

    {{-- Navigation — close drawer on link tap (mobile) --}}
    <nav id="erp-sidebar-nav" class="flex-1 overflow-y-auto erp-sidebar-scroll py-2 px-2"
         @click="if (window.matchMedia('(max-width: 1023px)').matches && $event.target.closest('a')) sidebarOpen = false">

        <div class="erp-nav-search">
            <label for="erp-nav-search-input" class="sr-only">Search menu</label>
            <div class="erp-nav-search-field">
                <svg class="erp-nav-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <input id="erp-nav-search-input"
                       type="text"
                       x-model="navSearch"
                       @input="filterSidebarNav()"
                       @keydown.escape="clearNavSearch()"
                       placeholder="Search menu…"
                       class="erp-nav-search-input"
                       autocomplete="off"
                       spellcheck="false">
                <button type="button"
                        x-show="navSearch.length"
                        x-cloak
                        @click="clearNavSearch()"
                        class="erp-nav-search-clear"
                        aria-label="Clear search">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <p x-show="navSearchEmpty" x-cloak class="erp-nav-search-empty">No menu found.</p>
        </div>

        <p class="erp-nav-section" data-nav-section>Operations</p>

        @if(auth()->user()->hasPermission('orders.view'))
            <a href="{{ route('admin.requirements.index') }}"
               data-nav-label="Operations Requirements"
               class="erp-nav-link {{ request()->routeIs('admin.requirements.*') ? 'erp-nav-link-active' : '' }}">
                <svg class="w-4 h-4 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Requirements
            </a>
        @endif

        @include('partials.erp.dashboards-sidebar')

        @if(auth()->user()->hasAnyHrmViewPermission())
            <p class="erp-nav-section" data-nav-section>HRM</p>

            @include('partials.erp.hrm-sidebar-module', [
                'section' => 'employee',
                'label' => 'Employee',
                'openKey' => 'employee',
                'canViewAny' => auth()->user()->hasAnyEmployeeViewPermission(),
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
            ])

            @include('partials.erp.hrm-sidebar-module', [
                'section' => 'recruitment',
                'label' => 'Recruitment',
                'openKey' => 'recruitment',
                'canViewAny' => auth()->user()->hasAnyRecruitmentViewPermission(),
                'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            ])

            @include('partials.erp.hrm-sidebar-module', [
                'section' => 'attendance',
                'label' => 'Attendance',
                'openKey' => 'attendance',
                'canViewAny' => auth()->user()->hasAnyAttendanceViewPermission(),
                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            ])

            @include('partials.erp.hrm-sidebar-module', [
                'section' => 'leave',
                'label' => 'Leave',
                'openKey' => 'leave',
                'canViewAny' => auth()->user()->hasAnyLeaveViewPermission(),
                'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            ])

            @include('partials.erp.hrm-sidebar-module', [
                'section' => 'performance',
                'label' => 'Performance',
                'openKey' => 'performance',
                'canViewAny' => auth()->user()->hasAnyPerformanceViewPermission(),
                'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
            ])

            @include('partials.erp.hrm-sidebar-module', [
                'section' => 'salary',
                'label' => 'Salary',
                'openKey' => 'salary',
                'canViewAny' => auth()->user()->hasAnySalaryViewPermission(),
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ])

            @include('partials.erp.hrm-sidebar-module', [
                'section' => 'compliance',
                'label' => 'Compliance',
                'openKey' => 'compliance',
                'canViewAny' => auth()->user()->hasAnyComplianceViewPermission(),
                'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            ])

            @include('partials.erp.hrm-sidebar-module', [
                'section' => 'finance',
                'label' => 'Finance',
                'openKey' => 'finance',
                'canViewAny' => auth()->user()->hasAnyFinanceViewPermission(),
                'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
            ])

            @include('partials.erp.hrm-sidebar-module', [
                'section' => 'rmg',
                'label' => 'RMG Extras',
                'openKey' => 'rmg',
                'canViewAny' => auth()->user()->hasAnyRmgViewPermission(),
                'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            ])
        @endif

        @include('partials.erp.tms-sidebar')

        @if($canViewKb)
            <p class="erp-nav-section" data-nav-section>Help</p>
            <a href="{{ route('admin.kb.hub') }}"
               data-nav-label="Knowledge Base Workflow Guides"
               class="erp-nav-link {{ request()->routeIs('admin.kb.*') ? 'erp-nav-link-active' : '' }}">
                <svg class="w-4 h-4 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Knowledge Base
            </a>
        @endif

        @if(auth()->user()->hasAnyMasterViewPermission() || auth()->user()->hasAnyHrmMasterViewPermission())
        <p class="erp-nav-section" data-nav-section>Core Modules</p>    
        @include('partials.erp.master-data-sidebar')
        @endif

        @if(auth()->user()->hasPermission('users.manage') || auth()->user()->hasPermission('roles.manage') || auth()->user()->hasPermission('settings.manage'))
            <p class="erp-nav-section" data-nav-section>Administration</p>

            <div data-nav-branch>
            <button type="button" @click="adminOpen = !adminOpen"
                    data-nav-label="Administration System Admin"
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

            <div x-show="adminOpen" x-cloak class="erp-nav-sub" data-nav-open-key="admin">
                @if(auth()->user()->hasPermission('users.manage'))
                    <a href="{{ route('admin.users.index') }}"
                       data-nav-label="Administration Users"
                       class="erp-nav-sub-link {{ request()->routeIs('admin.users.*') ? 'erp-nav-sub-link-active' : '' }}">
                        Users
                    </a>
                @endif
                @if(auth()->user()->hasPermission('roles.manage'))
                    <a href="{{ route('admin.roles.index') }}"
                       data-nav-label="Administration Roles Permissions"
                       class="erp-nav-sub-link {{ request()->routeIs('admin.roles.*') ? 'erp-nav-sub-link-active' : '' }}">
                        Roles & Permissions
                    </a>
                @endif
                @if(auth()->user()->hasPermission('settings.manage'))
                    <a href="{{ route('admin.settings.edit') }}"
                       data-nav-label="Administration App Settings"
                       class="erp-nav-sub-link {{ request()->routeIs('admin.settings.*') ? 'erp-nav-sub-link-active' : '' }}">
                        App Settings
                    </a>
                @endif
                <a href="{{ route('admin.profile.edit') }}"
                   data-nav-label="Administration My Profile"
                   class="erp-nav-sub-link {{ request()->routeIs('admin.profile.*') ? 'erp-nav-sub-link-active' : '' }}">
                    My Profile
                </a>
            </div>
            </div>
        @elseif(auth()->check())
            <p class="erp-nav-section" data-nav-section>Account</p>
            <a href="{{ route('admin.profile.edit') }}"
               data-nav-label="Account My Profile"
               class="erp-nav-link {{ request()->routeIs('admin.profile.*') ? 'erp-nav-link-active' : '' }}">
                My Profile
            </a>
        @endif
    </nav>

    {{-- Footer --}}
    <div class="erp-sidebar-footer p-3 shrink-0">
        <a href="{{ route('orders.create') }}" target="_blank">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Public Requirement Form
        </a>
    </div>
</aside>
