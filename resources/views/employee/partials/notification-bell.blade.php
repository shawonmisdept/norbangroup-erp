@auth('employee')
    @php
        $portalUser = auth('employee')->user();
        $unreadCount = $portalUser->unreadNotifications()->count();
    @endphp
    <div class="relative shrink-0" x-data="notificationBell({{ $unreadCount }}, '{{ route('employee.notifications.unread-count') }}')">
        <a href="{{ route('employee.notifications.index') }}"
           class="relative flex h-9 w-9 items-center justify-center rounded-xl text-white/80 hover:bg-white/10 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span x-show="unreadCount > 0" x-cloak
                  class="absolute top-0.5 right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-gold text-white text-[9px] font-bold flex items-center justify-center leading-none"
                  x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
        </a>
    </div>
@endauth
