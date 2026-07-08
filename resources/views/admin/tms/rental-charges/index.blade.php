@extends('layouts.admin')
@section('title', 'Rental Charges')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Rental Charges',
    'subtitle' => 'Daily KM billing for rental vehicles — mark paid or undo',
    'actions' => '<a href="' . route('admin.tms.reports.index', ['tab' => 'rental_charges']) . '" class="erp-btn-secondary">Reports</a>',
])

<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
    @if($factories !== [])
        <div>
            <label class="erp-label">Unit</label>
            <select name="factory_id" class="erp-input">
                <option value="">All</option>
                @foreach($factories as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['factory_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div>
        <label class="erp-label">Status</label>
        <select name="payment_status" class="erp-input">
            <option value="">All</option>
            <option value="pending" @selected(($filters['payment_status'] ?? '') === 'pending')>Pending</option>
            <option value="paid" @selected(($filters['payment_status'] ?? '') === 'paid')>Paid</option>
        </select>
    </div>
    <div>
        <label class="erp-label">Vendor</label>
        <select name="rental_vendor_id" class="erp-input">
            <option value="">All</option>
            @foreach($vendors as $id => $name)
                <option value="{{ $id }}" @selected(($filters['rental_vendor_id'] ?? '') == $id)>{{ $name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="erp-label">From</label>
        <input type="date" name="from" class="erp-input" value="{{ $filters['from'] ?? '' }}">
    </div>
    <div>
        <label class="erp-label">To</label>
        <input type="date" name="to" class="erp-input" value="{{ $filters['to'] ?? '' }}">
    </div>
    <button type="submit" class="erp-btn-primary">Filter</button>
</form>

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Vehicle</th>
                <th>Vendor</th>
                <th>KM</th>
                <th>Rate</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Paid At</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($charges as $c)
                <tr>
                    <td class="text-xs tabular-nums">{{ $c->log_date?->format('d M Y') ?? '—' }}</td>
                    <td class="text-xs">{{ $c->vehicle?->displayLabel() ?? '—' }}</td>
                    <td class="text-xs">{{ $c->rentalVendor?->name ?? '—' }}</td>
                    <td class="tabular-nums">{{ number_format((float) $c->total_km, 2) }}</td>
                    <td class="tabular-nums">৳{{ number_format((float) $c->km_rate, 2) }}</td>
                    <td class="tabular-nums font-medium">৳{{ number_format((float) $c->amount, 2) }}</td>
                    <td>
                        <span class="erp-badge {{ $c->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ ucfirst($c->payment_status) }}
                        </span>
                    </td>
                    <td class="text-xs">{{ $c->paid_at?->format('d M Y') ?? '—' }}</td>
                    <td class="text-right whitespace-nowrap">
                        @if($c->trip_log_id)
                            <a href="{{ route('admin.tms.trips.show', $c->trip_log_id) }}" class="erp-btn-sm-secondary">Trip</a>
                        @endif
                        @if($canManage)
                            @if($c->payment_status === 'pending')
                                <form method="POST" action="{{ route('admin.tms.rental-charges.mark-paid', $c) }}" class="inline"
                                      data-confirm="Mark as paid?"
                                      data-confirm-variant="primary"
                                      data-confirm-ok="Yes, mark paid">
                                    @csrf
                                    <button type="submit" class="erp-btn-sm-primary">Mark Paid</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.tms.rental-charges.unmark-paid', $c) }}" class="inline"
                                      data-confirm="Unmark as paid?"
                                      data-confirm-variant="warning"
                                      data-confirm-ok="Yes, unmark">
                                    @csrf
                                    <button type="submit" class="erp-btn-sm-secondary">Unmark Paid</button>
                                </form>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center py-8 text-gray-400">No rental charges found.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($charges->hasPages())
        <div class="px-4 py-3 border-t">{{ $charges->links() }}</div>
    @endif
</div>
@endsection
