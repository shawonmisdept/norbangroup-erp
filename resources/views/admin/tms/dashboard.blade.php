@extends('layouts.admin')
@section('title', 'TMS Dashboard')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Transport Dashboard',
    'subtitle' => 'Pending requests, active trips, and payment summary',
    'actions' => view('admin.tms.partials.dashboard-quick-actions', ['quickActions' => $quickActions ?? []])->render(),
])

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500">Pending Requests</p>
        <p class="text-2xl font-bold tabular-nums">{{ $pendingRequests }}</p>
    </div>
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500">Active Trips</p>
        <p class="text-2xl font-bold tabular-nums">{{ $activeTrips }}</p>
    </div>
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500">OT Pending</p>
        <p class="text-2xl font-bold tabular-nums">{{ $otPending }}</p>
    </div>
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500">Rental Charges Pending</p>
        <p class="text-2xl font-bold tabular-nums">{{ $rentalChargesPending }}</p>
    </div>
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500">Maintenance Bills (This Month)</p>
        <p class="text-2xl font-bold tabular-nums">{{ $maintenanceBillsThisMonth }}</p>
    </div>
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500">Maintenance Spend (This Month)</p>
        <p class="text-2xl font-bold tabular-nums">৳{{ number_format($maintenanceSpendThisMonth, 0) }}</p>
    </div>
    <a href="{{ route('admin.tms.vehicles.papers', ['paper_status' => 'expired']) }}" class="erp-panel p-4 hover:bg-red-50 transition {{ $papersExpired ? 'ring-1 ring-red-200' : '' }}">
        <p class="text-xs text-gray-500">Papers Expired</p>
        <p class="text-2xl font-bold tabular-nums text-red-700">{{ $papersExpired }}</p>
    </a>
    <a href="{{ route('admin.tms.vehicles.papers', ['paper_status' => 'urgent']) }}" class="erp-panel p-4 hover:bg-orange-50 transition {{ $papersUrgent ? 'ring-1 ring-orange-200' : '' }}">
        <p class="text-xs text-gray-500">Papers Urgent (≤30d)</p>
        <p class="text-2xl font-bold tabular-nums text-orange-700">{{ $papersUrgent }}</p>
    </a>
    <a href="{{ route('admin.tms.vehicles.papers', ['paper_status' => 'warning']) }}" class="erp-panel p-4 hover:bg-amber-50 transition">
        <p class="text-xs text-gray-500">Papers Warning (≤60d)</p>
        <p class="text-2xl font-bold tabular-nums text-amber-700">{{ $papersWarning }}</p>
    </a>
</div>

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Employee</th>
                <th>Pickup</th>
                <th>When</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentRequests as $req)
                <tr>
                    <td class="tabular-nums">#{{ $req->id }}</td>
                    <td>{{ $req->employee?->name }}</td>
                    <td class="text-xs max-w-[200px] truncate">{{ $req->pickup_location }}</td>
                    <td>@include('partials.erp.datetime-highlight', ['at' => $req->pickup_at, 'variant' => 'admin'])</td>
                    <td><span class="erp-badge {{ $req->statusBadgeClass() }}">{{ $req->statusLabel() }}</span></td>
                    <td class="text-right">
                        @if(auth()->user()->canViewTmsSubmodule('requests'))
                            @include('partials.erp.table-actions', ['viewUrl' => route('admin.tms.requests.show', $req)])
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-8 text-gray-400">No requests yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
