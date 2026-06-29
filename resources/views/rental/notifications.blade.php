@extends('layouts.rental')
@section('title', 'Notifications')
@section('page-title', 'Notifications')
@section('back', route('rental.dashboard'))
@section('content')
<div class="space-y-3">
@if($notifications->count())
<form method="POST" action="{{ route('rental.notifications.read-all') }}" class="text-right">@csrf @method('PATCH')
<button type="submit" class="text-xs text-brand font-semibold">Mark all read</button>
</form>
@endif

@forelse($notifications as $notification)
@php $data = $notification->data; @endphp
<form method="POST" action="{{ route('rental.notifications.read', $notification->id) }}">
@csrf @method('PATCH')
<button type="submit" class="emp-card block w-full text-left p-4 {{ $notification->read_at ? 'opacity-60' : '' }}">
<p class="font-semibold text-sm">{{ $data['title'] ?? 'Notification' }}</p>
<p class="text-xs text-gray-500 mt-1">{{ $data['message'] ?? '' }}</p>
<p class="text-[10px] text-gray-400 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
</button>
</form>
@empty
<p class="text-center text-gray-400 py-8 text-sm">No notifications yet.</p>
@endforelse

@if($notifications->hasPages())<div class="pt-2">{{ $notifications->links() }}</div>@endif
</div>
@endsection
