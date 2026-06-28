@auth('employee')
    @php
        $portalUser = auth('employee')->user();
        $unreadCount = $portalUser->unreadNotifications()->count();
        $recentNotifications = $portalUser->notifications()->limit(10)->get();
        $typeMeta = [
            'leave_approval_required' => 'Leave',
            'leave_status'            => 'Leave',
            'payslip_ready'           => 'Payslip',
            'loan_rejected'           => 'Loan',
            'loan_settled'            => 'Loan',
            'advance_disbursed'       => 'Advance',
            'final_settlement_paid'   => 'F&F',
            'roster_published'        => 'Roster',
            'promotion_status'        => 'Promo',
            'separation_status'       => 'Exit',
            'performance_approved'    => 'Perf',
            'performance_rejected'    => 'Perf',
            'hrm_daily_attendance'    => 'Team',
            'tms_request_approved'    => 'Transport',
            'tms_request_rejected'    => 'Transport',
            'tms_trip_assigned'       => 'Transport',
            'tms_trip_started'        => 'Transport',
            'tms_trip_completed'      => 'Transport',
        ];
    @endphp
    <div class="relative z-50 shrink-0" x-data="notificationBell({{ $unreadCount }}, '{{ route('employee.notifications.unread-count') }}', true)">
        <button type="button" x-ref="trigger" @click="toggle()" @click.outside="open = false"
                class="relative flex h-9 w-9 items-center justify-center rounded-xl text-white/80 hover:bg-white/10 transition"
                title="Notifications">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span x-show="unreadCount > 0" x-cloak
                  class="absolute top-0.5 right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-gold text-white text-[9px] font-bold flex items-center justify-center leading-none"
                  x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
        </button>

        <div x-show="open" x-cloak x-transition
             :style="panelStyle"
             class="fixed z-[200] w-80 max-w-[calc(100vw-1.5rem)] bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden text-gray-900">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50">
                <div>
                    <p class="text-xs font-bold text-gray-800 uppercase tracking-wide">Notifications</p>
                    <p class="text-[10px] text-gray-400 mt-0.5" x-show="unreadCount > 0" x-cloak>
                        <span x-text="unreadCount"></span> unread
                    </p>
                </div>
                @if($unreadCount > 0)
                    <form method="POST" action="{{ route('employee.notifications.read-all') }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-[10px] font-semibold text-brand hover:text-brand-dark uppercase tracking-wide">
                            Mark all read
                        </button>
                    </form>
                @endif
            </div>

            <div class="max-h-72 overflow-y-auto">
                @forelse($recentNotifications as $notification)
                    @php
                        $data = $notification->data;
                        $type = $data['type'] ?? 'general';
                        $label = $typeMeta[$type] ?? 'Alert';
                    @endphp
                    <form method="POST" action="{{ route('employee.notifications.read', $notification->id) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="w-full text-left px-4 py-3 border-b border-gray-100 hover:bg-brand/[0.03] transition {{ $notification->read_at ? 'opacity-55' : 'bg-brand/[0.02]' }}">
                            <div class="flex items-start gap-2.5">
                                <span class="shrink-0 mt-0.5 rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide bg-brand/10 text-brand">{{ $label }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-gray-800 leading-snug">{{ $data['title'] ?? 'Notification' }}</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5 leading-snug line-clamp-2">{{ $data['message'] ?? '' }}</p>
                                    <p class="text-[10px] text-gray-400 mt-1.5">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                                @unless($notification->read_at)
                                    <span class="w-2 h-2 rounded-full bg-gold shrink-0 mt-1.5" title="Unread"></span>
                                @endunless
                            </div>
                        </button>
                    </form>
                @empty
                    <div class="px-4 py-8 text-center text-xs text-gray-400">No notifications yet.</div>
                @endforelse
            </div>

            <div class="border-t border-gray-100 px-4 py-2 bg-gray-50">
                <a href="{{ route('employee.notifications.index') }}" class="block text-center text-[11px] font-semibold text-brand hover:text-brand-dark">
                    View all notifications
                </a>
            </div>
        </div>
    </div>
@endauth
