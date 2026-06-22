<header class="h-14 bg-white border-b border-erp-border flex items-center justify-between px-4 lg:px-6 shrink-0 sticky top-0 z-30">

    <div class="flex items-center gap-3 min-w-0">
        <button type="button" @click="sidebarOpen = !sidebarOpen"
                class="lg:hidden p-1.5 -ml-1 text-gray-500 hover:text-gray-800 hover:bg-gray-100 rounded-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/>
            </svg>
        </button>

        @hasSection('breadcrumbs')
            <nav class="hidden sm:flex items-center gap-1.5 text-xs text-gray-400 min-w-0">
                @yield('breadcrumbs')
            </nav>
        @else
            <span class="text-xs text-gray-400 hidden sm:block">{{ now()->format('l, d M Y') }}</span>
        @endif
    </div>

    <div class="flex items-center gap-2 shrink-0">
        @if(auth()->user()->hasPermission('orders.view'))
            @include('partials.erp.notification-bell')
        @endif

        <div class="hidden md:flex items-center gap-2 text-right">
            <div>
                <p class="text-xs font-semibold text-gray-800 leading-tight">{{ auth()->user()->name }}</p>
                <p class="text-[10px] text-gray-400 leading-tight">{{ auth()->user()->roleLabel() }}</p>
            </div>
            @include('partials.user-avatar', ['user' => auth()->user(), 'size' => '32'])
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="erp-btn-secondary !py-1.5 !px-2.5 text-[11px]"
                    title="Logout">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="hidden sm:inline">Logout</span>
            </button>
        </form>
    </div>
</header>
