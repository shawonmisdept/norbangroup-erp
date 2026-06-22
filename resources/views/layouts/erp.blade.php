<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name') . ' — ERP')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $activeModule = request()->route('module');
        $activeMasterGroup = null;
        if ($activeModule) {
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
    @endphp
</head>
<body class="bg-erp-bg text-gray-900 antialiased min-h-screen"
      x-data='{
          sidebarOpen: false,
          openGroups: @json($initialOpenGroups),
          adminOpen: {{ request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.profile.*', 'admin.settings.*') ? 'true' : 'false' }}
      }'>

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black/50 lg:hidden" x-cloak></div>

    <div class="flex min-h-screen">

        @include('partials.erp.sidebar')

        <div class="flex-1 flex flex-col min-w-0 lg:pl-64">

            @include('partials.erp.topbar')

            @include('partials.erp.toast')

            <main class="flex-1 p-4 lg:p-6">
                @yield('admin-content')
            </main>

            <footer class="px-4 lg:px-6 py-3 border-t border-erp-border bg-white text-[11px] text-gray-400">
                &copy; 1990 - {{ date('Y') }} Norban Group — A Product of Data State Ltd
            </footer>
        </div>
    </div>

    <style>[x-cloak]{display:none!important}</style>
</body>
</html>
