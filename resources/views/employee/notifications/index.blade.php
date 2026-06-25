@extends('layouts.employee')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
<div class="space-y-3 pb-4">
    @if(auth('employee')->user()->unreadNotifications()->count() > 0)
        <form method="POST" action="{{ route('employee.notifications.read-all') }}">
            @csrf @method('PATCH')
            <button type="submit" class="emp-btn-sm-secondary">Mark all read</button>
        </form>
    @endif

    @forelse($notifications as $notification)
        @php
            $data = $notification->data;
            $isUnread = ! $notification->read_at;
        @endphp
        <form method="POST" action="{{ route('employee.notifications.read', $notification->id) }}">
            @csrf @method('PATCH')
            <button type="submit" class="w-full text-left emp-card {{ $isUnread ? 'ring-1 ring-brand/20' : 'opacity-70' }}">
                <p class="text-sm font-semibold text-gray-900">{{ $data['title'] ?? 'Notification' }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $data['message'] ?? '' }}</p>
                <p class="text-[10px] text-gray-400 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
            </button>
        </form>
    @empty
        <div class="emp-card text-center py-10 text-sm text-gray-400">No notifications yet.</div>
    @endforelse

    {{ $notifications->links() }}
</div>
@endsection
