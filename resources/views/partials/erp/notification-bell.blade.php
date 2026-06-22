<div class="relative" x-data="{ open: false }">
    @php
        $unreadCount = auth()->user()->unreadNotifications()->count();
        $recentNotifications = auth()->user()->notifications()->limit(8)->get();
    @endphp

    <button type="button" @click="open = !open" @click.outside="open = false"
            class="relative p-2 rounded-sm text-gray-500 hover:text-brand hover:bg-gray-100 transition"
            title="Notifications">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        @if($unreadCount > 0)
            <span class="absolute top-1 right-1 min-w-[16px] h-4 px-1 rounded-full bg-gold text-white text-[9px] font-bold flex items-center justify-center leading-none">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition
         class="absolute right-0 mt-2 w-80 bg-white border border-erp-border rounded-sm shadow-lg z-50 overflow-hidden">
        <div class="flex items-center justify-between px-3 py-2.5 border-b border-erp-border bg-gray-50/80">
            <p class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Notifications</p>
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="text-[10px] font-semibold text-brand hover:text-brand-dark uppercase tracking-wide">
                        Mark all read
                    </button>
                </form>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto erp-sidebar-scroll">
            @forelse($recentNotifications as $notification)
                @php $data = $notification->data; @endphp
                <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="w-full text-left px-3 py-2.5 border-b border-gray-100 hover:bg-blue-50/40 transition {{ $notification->read_at ? 'opacity-60' : 'bg-brand/5' }}">
                        <p class="text-xs font-semibold text-gray-800">{{ $data['title'] ?? 'Notification' }}</p>
                        <p class="text-[11px] text-gray-500 mt-0.5 leading-snug">{{ $data['message'] ?? '' }}</p>
                        <p class="text-[10px] text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </button>
                </form>
            @empty
                <p class="px-4 py-8 text-center text-xs text-gray-400">No notifications yet.</p>
            @endforelse
        </div>
    </div>
</div>
