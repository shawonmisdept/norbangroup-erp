@extends('layouts.employee')
@section('title', 'Transport')
@section('content')
<div class="space-y-4">
<div class="flex items-center justify-between">
<h1 class="text-lg font-bold">My Transport</h1>
<a href="{{ route('employee.transport.requests.create') }}" class="emp-btn-sm">New Request</a>
</div>
@if($isDriver)
<a href="{{ route('employee.transport.trips') }}" class="emp-card block p-4 text-sm">
<span class="font-semibold">Driver Trips</span>
<p class="text-gray-500 text-xs mt-1">Start or end assigned trips</p>
</a>
<a href="{{ route('employee.transport.odometer') }}" class="emp-card block p-4 text-sm">
<span class="font-semibold">Daily KM Log</span>
<p class="text-gray-500 text-xs mt-1">Record morning and evening odometer</p>
</a>
@endif
@forelse($requests as $req)
<a href="{{ route('employee.transport.requests.show', $req) }}" class="emp-card block p-4">
<div class="flex justify-between gap-2">
<div class="min-w-0">
<p class="font-medium truncate">{{ $req->destinationLabel() }}</p>
<p class="text-xs text-gray-500 mt-1">@include('partials.erp.datetime-highlight', ['at' => $req->pickup_at, 'variant' => 'employee'])</p>
</div>
@include('partials.erp.tms-status-badge', ['status' => $req->status, 'label' => $req->statusLabel(), 'variant' => 'employee', 'class' => 'shrink-0'])
</div>
</a>
@empty
<p class="text-center text-gray-400 py-8 text-sm">No transport requests yet.</p>
@endforelse
@if($requests->hasPages())<div class="pt-2">{{ $requests->links() }}</div>@endif
</div>
@endsection
