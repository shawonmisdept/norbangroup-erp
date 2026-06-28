<div class="relative" x-data="notificationBell({{ auth()->user()->unreadNotifications()->count() }}, '{{ route('admin.notifications.unread-count') }}')">
    @php
        $recentNotifications = auth()->user()->notifications()->limit(12)->get();
        $typeMeta = [
            'new_requirement' => ['label' => 'Order', 'class' => 'erp-notif-badge-order'],
            'status_updated' => ['label' => 'Order', 'class' => 'erp-notif-badge-order'],
            'hrm_late_acceptance' => ['label' => 'HRM', 'class' => 'erp-notif-badge-hrm'],
            'hrm_unmapped_punch' => ['label' => 'HRM', 'class' => 'erp-notif-badge-hrm'],
            'hrm_manual_punch' => ['label' => 'HRM', 'class' => 'erp-notif-badge-hrm'],
            'hrm_sync_failed' => ['label' => 'HRM', 'class' => 'erp-notif-badge-hrm'],
            'hrm_leave_applied' => ['label' => 'Leave', 'class' => 'erp-notif-badge-hrm'],
            'hrm_leave_pending_hr' => ['label' => 'Leave', 'class' => 'erp-notif-badge-hrm'],
            'hrm_daily_attendance' => ['label' => 'HRM', 'class' => 'erp-notif-badge-hrm'],
            'hrm_contract_expiry' => ['label' => 'HRM', 'class' => 'erp-notif-badge-hrm'],
            'hrm_probation_end' => ['label' => 'HRM', 'class' => 'erp-notif-badge-hrm'],
            'hrm_ot_limit' => ['label' => 'HRM', 'class' => 'erp-notif-badge-hrm'],
            'advance_disbursed' => ['label' => 'Finance', 'class' => 'erp-notif-badge-hrm'],
            'loan_settled' => ['label' => 'Finance', 'class' => 'erp-notif-badge-hrm'],
            'loan_application' => ['label' => 'Finance', 'class' => 'erp-notif-badge-hrm'],
            'final_settlement_approved' => ['label' => 'F&F', 'class' => 'erp-notif-badge-hrm'],
            'final_settlement_calculated' => ['label' => 'F&F', 'class' => 'erp-notif-badge-hrm'],
            'final_settlement_pending' => ['label' => 'F&F', 'class' => 'erp-notif-badge-hrm'],
            'separation_submitted' => ['label' => 'Exit', 'class' => 'erp-notif-badge-hrm'],
            'separation_pending_hr' => ['label' => 'Exit', 'class' => 'erp-notif-badge-hrm'],
            'separation_approved' => ['label' => 'Exit', 'class' => 'erp-notif-badge-hrm'],
            'separation_rejected' => ['label' => 'Exit', 'class' => 'erp-notif-badge-hrm'],
            'promotion_pending' => ['label' => 'Promo', 'class' => 'erp-notif-badge-hrm'],
            'promotion_approved' => ['label' => 'Promo', 'class' => 'erp-notif-badge-hrm'],
            'promotion_rejected' => ['label' => 'Promo', 'class' => 'erp-notif-badge-hrm'],
            'recruitment_application' => ['label' => 'Recruit', 'class' => 'erp-notif-badge-hrm'],
            'roster_published' => ['label' => 'Roster', 'class' => 'erp-notif-badge-hrm'],
            'hrm_working_hours' => ['label' => 'HRM', 'class' => 'erp-notif-badge-hrm'],
            'hrm_performance_pending_hr' => ['label' => 'Perf', 'class' => 'erp-notif-badge-hrm'],
            'hrm_performance_pending_rating' => ['label' => 'Perf', 'class' => 'erp-notif-badge-hrm'],
            'gate_pass_pending' => ['label' => 'RMG', 'class' => 'erp-notif-badge-hrm'],
            'proxy_punch_flagged' => ['label' => 'RMG', 'class' => 'erp-notif-badge-hrm'],
            'worker_transfer_pending' => ['label' => 'RMG', 'class' => 'erp-notif-badge-hrm'],
            'manpower_variance' => ['label' => 'RMG', 'class' => 'erp-notif-badge-hrm'],
            'tms_request_submitted' => ['label' => 'TMS', 'class' => 'erp-notif-badge-hrm'],
            'tms_request_cancelled' => ['label' => 'TMS', 'class' => 'erp-notif-badge-hrm'],
            'tms_trip_started' => ['label' => 'TMS', 'class' => 'erp-notif-badge-hrm'],
            'tms_trip_completed' => ['label' => 'TMS', 'class' => 'erp-notif-badge-hrm'],
            'tms_ot_pending' => ['label' => 'TMS', 'class' => 'erp-notif-badge-hrm'],
        ];
    @endphp

    <button type="button" @click="open = !open" @click.outside="open = false"
            class="relative p-2 rounded-md text-gray-500 hover:text-brand hover:bg-brand/5 transition"
            title="Notifications">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span x-show="unreadCount > 0" x-cloak
              class="absolute top-0.5 right-0.5 min-w-[17px] h-[17px] px-1 rounded-full bg-gold text-white text-[9px] font-bold flex items-center justify-center leading-none shadow-sm"
              x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
    </button>

    <div x-show="open" x-cloak x-transition
         class="absolute right-0 mt-2 w-96 max-w-[calc(100vw-1.5rem)] bg-white border border-erp-border rounded-lg shadow-xl z-50 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-erp-border bg-gradient-to-r from-brand/[0.04] to-gold/5">
            <div>
                <p class="text-xs font-bold text-gray-800 uppercase tracking-wide">Notifications</p>
                <p class="text-[10px] text-gray-400 mt-0.5" x-show="unreadCount > 0" x-cloak>
                    <span x-text="unreadCount"></span> unread
                </p>
            </div>
            @if(auth()->user()->unreadNotifications()->count() > 0)
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="text-[10px] font-semibold text-brand hover:text-brand-dark uppercase tracking-wide">
                        Mark all read
                    </button>
                </form>
            @endif
        </div>

        <div class="max-h-[22rem] overflow-y-auto erp-sidebar-scroll">
            @forelse($recentNotifications as $notification)
                @php
                    $data = $notification->data;
                    $type = $data['type'] ?? 'general';
                    $meta = $typeMeta[$type] ?? ['label' => 'Alert', 'class' => 'erp-notif-badge-default'];
                @endphp
                <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="w-full text-left px-4 py-3 border-b border-gray-100 hover:bg-brand/[0.03] transition {{ $notification->read_at ? 'opacity-55' : 'bg-brand/[0.02]' }}">
                        <div class="flex items-start gap-2.5">
                            <span class="erp-notif-badge {{ $meta['class'] }} shrink-0 mt-0.5">{{ $meta['label'] }}</span>
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
                <div class="px-4 py-10 text-center">
                    <svg class="w-8 h-8 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p class="text-xs text-gray-400">No notifications yet.</p>
                </div>
            @endforelse
        </div>

        <div class="border-t border-erp-border px-4 py-2 bg-gray-50/80">
            <a href="{{ route('admin.notifications.index') }}" class="block text-center text-[11px] font-semibold text-brand hover:text-brand-dark">
                View all notifications
            </a>
        </div>
    </div>
</div>
