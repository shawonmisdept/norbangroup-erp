@extends('layouts.admin')

@section('title', 'Notifications')

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Notifications',
    'subtitle' => 'All alerts and updates for your account',
])

<div class="erp-panel">
    <div class="erp-panel-head flex items-center justify-between">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
            {{ auth()->user()->unreadNotifications()->count() }} unread
        </p>
        @if(auth()->user()->unreadNotifications()->count() > 0)
            <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                @csrf @method('PATCH')
                <button type="submit" class="text-xs font-semibold text-brand hover:text-brand-dark">Mark all read</button>
            </form>
        @endif
    </div>
    <div class="divide-y divide-erp-border">
        @forelse($notifications as $notification)
            @php
                $data = $notification->data;
                $isUnread = ! $notification->read_at;
            @endphp
            <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">
                @csrf @method('PATCH')
                <button type="submit" class="w-full text-left px-4 py-3 hover:bg-brand/[0.03] transition {{ $isUnread ? 'bg-brand/[0.02]' : 'opacity-60' }}">
                    <div class="flex items-start gap-3">
                        @if($isUnread)
                            <span class="w-2 h-2 rounded-full bg-gold shrink-0 mt-1.5"></span>
                        @else
                            <span class="w-2 shrink-0"></span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-800">{{ $data['title'] ?? 'Notification' }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $data['message'] ?? '' }}</p>
                            <p class="text-[10px] text-gray-400 mt-1">{{ $notification->created_at->format('d M Y H:i') }} · {{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </button>
            </form>
        @empty
            <div class="erp-panel-body text-center py-12 text-sm text-gray-400">No notifications yet.</div>
        @endforelse
    </div>
    @if($notifications->hasPages())
        <div class="erp-panel-body border-t border-erp-border">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
