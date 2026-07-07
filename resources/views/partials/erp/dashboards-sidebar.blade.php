@php
    $user = auth()->user();

    $hrmSections = [
        ['key' => 'employee', 'label' => 'Employee'],
        ['key' => 'recruitment', 'label' => 'Recruitment'],
        ['key' => 'attendance', 'label' => 'Attendance'],
        ['key' => 'leave', 'label' => 'Leave'],
        ['key' => 'performance', 'label' => 'Performance'],
        ['key' => 'salary', 'label' => 'Salary'],
        ['key' => 'compliance', 'label' => 'Compliance'],
        ['key' => 'finance', 'label' => 'Finance'],
        ['key' => 'rmg', 'label' => 'RMG Extras'],
    ];

    $canViewHrmSubmodule = function (string $section, string $key) use ($user): bool {
        return match ($section) {
            'employee' => $user->canViewEmployeeSubmodule($key),
            'recruitment' => $user->canViewRecruitmentSubmodule($key),
            'leave' => $user->canViewLeaveSubmodule($key),
            'performance' => $user->canViewPerformanceSubmodule($key),
            'attendance' => $user->canViewAttendanceSubmodule($key),
            'compliance' => $user->canViewComplianceSubmodule($key),
            'finance' => $user->canViewFinanceSubmodule($key),
            'rmg' => $user->canViewRmgSubmodule($key),
            default => $user->canViewSalarySubmodule($key),
        };
    };

    $dashboardLinks = [];

    if ($user->hasPermission('orders.view')) {
        $dashboardLinks[] = [
            'label' => 'Requirements',
            'route' => 'admin.requirements.index',
            'nav_label' => 'Dashboard Requirements',
            'active' => ['admin.requirements.*'],
        ];
    }

    if ($user->hasAnyHrmViewPermission()) {
        $dashboardLinks[] = [
            'label' => 'HRM',
            'route' => 'admin.hrm.dashboard',
            'nav_label' => 'Dashboard HRM',
            'active' => ['admin.hrm.dashboard', 'admin.hrm.dashboard.*'],
        ];
    }

    foreach ($hrmSections as $section) {
        $sub = config("hrm.{$section['key']}_submodules.dashboard");
        if (! $sub || ($sub['status'] ?? '') !== 'active') {
            continue;
        }
        if (! $canViewHrmSubmodule($section['key'], 'dashboard')) {
            continue;
        }

        $base = preg_replace('/\.(index|hub|dashboard)$/', '', $sub['route']);
        $dashboardLinks[] = [
            'label' => $section['label'],
            'route' => $sub['route'],
            'nav_label' => 'Dashboard HRM ' . $section['label'],
            'active' => [$base, $base . '.*'],
        ];
    }

    $tmsDashboard = config('tms.submodules.dashboard');
    if ($tmsDashboard
        && ($tmsDashboard['status'] ?? '') === 'active'
        && $user->canViewTmsSubmodule('dashboard')) {
        $base = preg_replace('/\.(index|hub|dashboard)$/', '', $tmsDashboard['route']);
        $dashboardLinks[] = [
            'label' => 'Transport',
            'route' => $tmsDashboard['route'],
            'nav_label' => 'Dashboard Transport',
            'active' => [$base, $base . '.*'],
        ];
    }

    $isDashboardActive = collect($dashboardLinks)->contains(
        fn (array $link) => collect($link['active'])->contains(
            fn (string $pattern) => request()->routeIs($pattern)
        )
    );
@endphp

@if($dashboardLinks !== [])
    <p class="erp-nav-section" data-nav-section>Dashboard</p>

    <div data-nav-branch>
        <button type="button"
                @click="openGroups['dashboards'] = !openGroups['dashboards']"
                data-nav-label="Dashboard All Dashboards"
                class="erp-nav-group {{ $isDashboardActive ? 'erp-nav-group-active' : '' }} w-full">
            <span class="erp-nav-group-icon bg-brand/10 text-brand">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="flex-1 text-left font-medium">All Dashboards</span>
            <span class="text-[10px] tabular-nums text-gray-400 mr-1">{{ count($dashboardLinks) }}</span>
            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform shrink-0"
                 :class="openGroups['dashboards'] && 'rotate-180'"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>

        <div x-show="openGroups['dashboards']" x-cloak class="erp-nav-sub" data-nav-open-key="dashboards">
            @foreach($dashboardLinks as $link)
                @php
                    $linkActive = collect($link['active'])->contains(
                        fn (string $pattern) => request()->routeIs($pattern)
                    );
                @endphp
                <a href="{{ route($link['route']) }}"
                   data-nav-label="{{ $link['nav_label'] }}"
                   class="erp-nav-sub-link {{ $linkActive ? 'erp-nav-sub-link-active' : '' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </div>
@endif
