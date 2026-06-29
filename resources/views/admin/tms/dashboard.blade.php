@extends('layouts.admin')
@section('title', 'TMS Dashboard')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Transport Dashboard', 'subtitle' => 'Pending requests, active trips, and payment summary'])
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    <div class="erp-panel p-4"><p class="text-xs text-gray-500">Pending Requests</p><p class="text-2xl font-bold tabular-nums">{{ $pendingRequests }}</p></div>
    <div class="erp-panel p-4"><p class="text-xs text-gray-500">Active Trips</p><p class="text-2xl font-bold tabular-nums">{{ $activeTrips }}</p></div>
    <div class="erp-panel p-4"><p class="text-xs text-gray-500">OT Pending</p><p class="text-2xl font-bold tabular-nums">{{ $otPending }}</p></div>
    <div class="erp-panel p-4"><p class="text-xs text-gray-500">Rental Charges Pending</p><p class="text-2xl font-bold tabular-nums">{{ $rentalChargesPending }}</p></div>
    <div class="erp-panel p-4"><p class="text-xs text-gray-500">Open Maintenance</p><p class="text-2xl font-bold tabular-nums">{{ $openMaintenance }}</p></div>
    <div class="erp-panel p-4"><p class="text-xs text-gray-500">Vehicles in Maintenance</p><p class="text-2xl font-bold tabular-nums">{{ $vehiclesInMaintenance }}</p></div>
</div>
<div class="erp-panel overflow-hidden">
<table class="erp-table">
<thead><tr><th>ID</th><th>Employee</th><th>Pickup</th><th>When</th><th>Status</th><th></th></tr></thead>
<tbody>
@forelse($recentRequests as $req)
<tr>
<td class="tabular-nums">#{{ $req->id }}</td>
<td>{{ $req->employee?->name }}</td>
<td class="text-xs max-w-[200px] truncate">{{ $req->pickup_location }}</td>
<td>@include('partials.erp.datetime-highlight', ['at' => $req->pickup_at, 'variant' => 'admin'])</td>
<td><span class="erp-badge {{ $req->statusBadgeClass() }}">{{ $req->statusLabel() }}</span></td>
<td class="text-right">@if(auth()->user()->canViewTmsSubmodule('requests'))@include('partials.erp.table-actions', ['viewUrl' => route('admin.tms.requests.show', $req)])@endif</td>
</tr>
@empty<tr><td colspan="6" class="text-center py-8 text-gray-400">No requests yet.</td></tr>@endforelse
</tbody></table>
</div>
@endsection
