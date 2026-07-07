<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', config('app.name') . ' — ERP')</title>
    @include('partials.erp.fonts-bengali')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @php
        $activeModule = request()->route('module');
        $activeMasterGroup = null;
        if ($activeModule && request()->routeIs('admin.masters.*')) {
            foreach (config('masters.groups') as $groupName => $modules) {
                if (in_array($activeModule, $modules, true)) {
                    $activeMasterGroup = $groupName;
                    break;
                }
            }
        }
        $initialOpenGroups = collect(config('masters.groups'))->keys()->mapWithKeys(function ($group) use ($activeMasterGroup) {
            $key = str_replace([' ', '&'], ['_', 'and'], $group);
            $open = $activeMasterGroup === $group
                || ($group === 'Commercial' && ! $activeMasterGroup && request()->routeIs('admin.masters.*'));
            return [$key => $open];
        })->all();

        $activeHrmModule = request()->routeIs('admin.hrm.masters.*') ? request()->route('module') : null;
        $activeHrmGroup = null;
        if ($activeHrmModule) {
            foreach (config('hrm.groups') as $groupName => $modules) {
                if (in_array($activeHrmModule, $modules, true)) {
                    $activeHrmGroup = $groupName;
                    break;
                }
            }
        }
        $initialHrmOpenGroups = collect(config('hrm.groups'))->keys()->mapWithKeys(function ($group) use ($activeHrmGroup) {
            $key = 'hrm_' . str_replace([' ', '&'], ['_', 'and'], $group);
            $open = $activeHrmGroup === $group
                || ($group === 'Organization' && ! $activeHrmGroup && request()->routeIs('admin.hrm.masters.*'));
            return [$key => $open];
        })->all();

        $hrmModuleGroupOpen = [];
        foreach (['attendance', 'leave', 'salary', 'compliance', 'finance', 'rmg'] as $hrmSection) {
            foreach (config("hrm.{$hrmSection}_nav_groups", []) as $groupLabel => $keys) {
                $gKey = $hrmSection . '_grp_' . str_replace([' ', '&'], ['_', 'and'], $groupLabel);
                $hrmModuleGroupOpen[$gKey] = collect($keys)->contains(
                    fn (string $key) => request()->routeIs(
                        'admin.hrm.' . $hrmSection . '.' . str_replace('-', '.', $key) . '*'
                    ) || request()->routeIs('admin.hrm.' . $hrmSection . '.' . $key . '*')
                );
            }
        }

        $tmsGroupOpen = [];
        foreach (config('tms.nav_groups', []) as $groupLabel => $keys) {
            $gKey = 'tms_grp_' . str_replace([' ', '&'], ['_', 'and'], $groupLabel);
            $tmsGroupOpen[$gKey] = collect($keys)->contains(function (string $key) {
                $sub = config("tms.submodules.{$key}");
                if (! $sub || ! isset($sub['route'])) {
                    return request()->routeIs('admin.tms.' . $key . '*')
                        || request()->routeIs('admin.tms.' . str_replace('_', '-', $key) . '*');
                }

                $base = preg_replace('/\.(index|hub|dashboard)$/', '', $sub['route']);

                return request()->routeIs($base) || request()->routeIs($base . '.*');
            });
        }

        $initialOpenGroups = array_merge(
            $initialOpenGroups,
            $initialHrmOpenGroups,
            $hrmModuleGroupOpen,
            $tmsGroupOpen,
            [
                'master_data' => request()->routeIs('admin.masters.*', 'admin.hrm.masters.*'),
                'attendance'  => request()->routeIs('admin.hrm.attendance.*'),
                'leave'       => request()->routeIs('admin.hrm.leave.*'),
                'salary'      => request()->routeIs('admin.hrm.salary.*'),
                'compliance'  => request()->routeIs('admin.hrm.compliance.*'),
                'finance'     => request()->routeIs('admin.hrm.finance.*'),
                'rmg'         => request()->routeIs('admin.hrm.rmg.*'),
                'employee'    => request()->routeIs('admin.hrm.employee.*', 'admin.hrm.employees.*', 'admin.hrm.separations.*', 'admin.hrm.promotions.*', 'admin.hrm.letters.*', 'admin.hrm.discipline.*'),
                'recruitment' => request()->routeIs('admin.hrm.recruitment.*'),
                'performance' => request()->routeIs('admin.hrm.performance.*'),
                'tms'         => request()->routeIs('admin.tms.*'),
            ]
        );

    @endphp
</head>
<body class="bg-erp-bg text-gray-900 antialiased min-h-screen"
      :class="{ 'sidebar-open': sidebarOpen }"
      x-data='erpShell(@json($initialOpenGroups), {{ request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.profile.*', 'admin.settings.*') ? 'true' : 'false' }})'>

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black/50 lg:hidden" x-cloak></div>

    <div class="flex min-h-screen">

        @include('partials.erp.sidebar')

        <div class="flex-1 flex flex-col min-w-0 lg:pl-64">

            @include('partials.erp.topbar')

            @include('partials.erp.toast')

            <main class="flex-1 p-3 sm:p-4 lg:p-6 min-w-0">
                @include('partials.erp.flash-messages')
                @yield('admin-content')
            </main>

            <footer class="px-3 sm:px-4 lg:px-6 py-3 border-t border-erp-border bg-white text-[11px] text-gray-400 text-center sm:text-left">
                &copy; 1990 - {{ date('Y') }} Norban Group — A Product of Data State Ltd
            </footer>
        </div>
    </div>

    <style>[x-cloak]{display:none!important}</style>
    @include('partials.confirm-dialog')
    @stack('scripts')
</body>
</html>
