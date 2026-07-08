@extends('layouts.admin')
@section('title', 'Transport Requests')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Transport Requests',
    'subtitle' => 'Select pending requests to merge and assign by driver vehicle capacity',
])

@include('admin.tms.partials.request-filters', ['filters' => $filters, 'factories' => $factories, 'statuses' => $statuses])

@if(auth()->user()->hasPermission('tms.requests.approve'))
    <form method="POST" action="{{ route('admin.tms.requests.merge') }}" id="merge-form" class="erp-panel p-4 mb-4 space-y-3"
          data-confirm="Merge selected requests and assign to this driver?"
          data-confirm-variant="primary"
          data-confirm-ok="Yes, merge & assign">
        @csrf
        @include('admin.tms.requests.partials.driver-assignment-fields', [
            'drivers' => $drivers,
            'rentalDrivers' => $rentalDrivers,
            'vehicles' => $vehicles,
            'vehiclePaperWarnings' => $vehiclePaperWarnings ?? [],
        ])
        <div class="flex gap-2">
            <button type="submit" class="erp-btn-primary flex-1">Merge & Assign</button>
        </div>
        <p class="text-xs text-gray-500">Selected passengers: <strong id="selected-passengers">0</strong> · Vehicle capacity: <strong id="vehicle-capacity">—</strong></p>
    </form>
    @include('admin.tms.requests.partials.driver-assignment-script')
@endif

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                @if(auth()->user()->hasPermission('tms.requests.approve'))
                    <th class="w-8"></th>
                @endif
                <th>ID</th>
                <th>Employee</th>
                <th>Pickup</th>
                <th>Destination</th>
                <th>When</th>
                <th>Pax</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $req)
                <tr>
                    @if(auth()->user()->hasPermission('tms.requests.approve'))
                        <td>
                            @if($req->status === 'pending')
                                <input type="checkbox" form="merge-form" name="request_ids[]" value="{{ $req->id }}" class="merge-check rounded" data-passengers="{{ $req->passenger_count }}">
                            @endif
                        </td>
                    @endif
                    <td class="tabular-nums">#{{ $req->id }}</td>
                    <td>{{ $req->employee?->name }}</td>
                    <td class="text-xs max-w-[160px]">{{ $req->pickup_location }}</td>
                    <td class="text-xs">{{ $req->destinationLabel() }}</td>
                    <td>@include('partials.erp.datetime-highlight', ['at' => $req->pickup_at, 'variant' => 'admin'])</td>
                    <td class="tabular-nums text-center">{{ $req->passenger_count }}</td>
                    <td><span class="erp-badge {{ $req->statusBadgeClass() }}">{{ $req->statusLabel() }}</span></td>
                    <td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.tms.requests.show', $req)])</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center py-8 text-gray-400">No requests.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($requests->hasPages())
        <div class="px-4 py-3 border-t">{{ $requests->links() }}</div>
    @endif
</div>

@if(auth()->user()->hasPermission('tms.requests.approve'))
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const capEl = document.getElementById('vehicle-capacity');
        const paxEl = document.getElementById('selected-passengers');
        const checks = () => document.querySelectorAll('.merge-check:checked');

        function refresh() {
            let total = 0;
            checks().forEach(c => total += parseInt(c.dataset.passengers || '1', 10));
            paxEl.textContent = total;
        }

        function refreshCapacity() {
            const vehicle = document.querySelector('#merge-form .assign-vehicle-select');
            const opt = vehicle?.selectedOptions[0];
            if (opt?.value) {
                capEl.textContent = opt.dataset.capacity || '—';
                return;
            }
            const typeSelect = document.querySelector('#merge-form .driver-type-select');
            const isCompany = typeSelect?.value === 'company';
            const driverOpt = isCompany
                ? document.querySelector('#merge-form .company-driver-select')?.selectedOptions[0]
                : document.querySelector('#merge-form .rental-driver-select')?.selectedOptions[0];
            capEl.textContent = driverOpt?.dataset.capacity || '—';
        }

        document.querySelector('#merge-form .assign-vehicle-select')?.addEventListener('change', refreshCapacity);
        document.querySelector('#merge-form .company-driver-select')?.addEventListener('change', refreshCapacity);
        document.querySelector('#merge-form .rental-driver-select')?.addEventListener('change', refreshCapacity);
        document.querySelector('#merge-form .driver-type-select')?.addEventListener('change', refreshCapacity);
        document.querySelectorAll('.merge-check').forEach(c => c.addEventListener('change', refresh));
        refreshCapacity();
    });
    </script>
@endif
@endsection
