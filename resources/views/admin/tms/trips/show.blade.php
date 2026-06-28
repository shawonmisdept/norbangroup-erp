@extends('layouts.admin')
@section('title', 'Trip #' . $trip->id)
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Trip #' . $trip->id,
    'subtitle' => 'Trip log details',
    'actions' => '<a href="' . route('admin.tms.trips.index') . '" class="erp-btn-secondary">← Back</a>',
])
<div class="erp-panel p-6 max-w-3xl space-y-3 text-sm">
<p class="flex flex-wrap items-center gap-2"><span class="text-gray-500">Status:</span> @include('partials.erp.tms-status-badge', ['status' => $trip->trip_status, 'label' => $trip->tripStatusLabel(), 'type' => 'trip'])</p>
<p><span class="text-gray-500">Vehicle:</span> {{ $trip->vehicle?->displayLabel() }}</p>
<p><span class="text-gray-500">Driver:</span> {{ $trip->driver?->displayLabel() }}</p>
<p><span class="text-gray-500">Total passengers:</span> {{ $trip->total_passengers }}</p>
<div>
<p class="text-gray-500 mb-2">Passengers & pickup:</p>
<ul class="space-y-2">
@forelse($trip->transportRequests as $r)
<li class="rounded border border-gray-100 bg-gray-50 px-3 py-2 flex flex-wrap items-start justify-between gap-2">
<div>
<span class="font-medium text-gray-900">{{ $r->employee?->name }}</span>
<span class="text-gray-500"> — Pickup:</span> <strong>{{ $r->pickup_location }}</strong>
<span class="text-gray-500"> →</span> {{ $r->destinationLabel() }}
<span class="text-gray-400 text-xs">@include('partials.erp.datetime-highlight', ['at' => $r->pickup_at, 'variant' => 'admin'])</span>
</div>
<a href="{{ route('admin.tms.requests.show', $r) }}" class="erp-btn-sm-secondary shrink-0">View</a>
</li>
@empty
@if($trip->transportRequest)
<li class="rounded border border-gray-100 bg-gray-50 px-3 py-2 flex flex-wrap items-start justify-between gap-2">
<div>
<span class="font-medium text-gray-900">{{ $trip->transportRequest->employee?->name }}</span>
<span class="text-gray-500"> — Pickup:</span> <strong>{{ $trip->transportRequest->pickup_location }}</strong>
<span class="text-gray-500"> →</span> {{ $trip->transportRequest->destinationLabel() }}
</div>
<a href="{{ route('admin.tms.requests.show', $trip->transportRequest) }}" class="erp-btn-sm-secondary shrink-0">View</a>
</li>
@else
<li>—</li>
@endif
@endforelse
</ul>
</div>
<p class="flex flex-wrap items-center gap-2"><span class="text-gray-500">Duty:</span>
@include('partials.erp.datetime-highlight', ['at' => $trip->duty_start_at, 'variant' => 'admin'])
<span class="text-gray-400">→</span>
@include('partials.erp.datetime-highlight', ['at' => $trip->duty_end_at, 'variant' => 'admin'])
</p>
<p><span class="text-gray-500">OT:</span> {{ $trip->ot_hours }} hrs · ৳{{ number_format($trip->ot_amount, 2) }}</p>
@if($trip->overtimePayment)
<p><span class="text-gray-500">OT Payment:</span> {{ ucfirst($trip->overtimePayment->payment_status) }}
@if($trip->overtimePayment->payment_status === 'pending' && auth()->user()->hasPermission('tms.overtime.manage'))
<form method="POST" action="{{ route('admin.tms.trips.mark-ot-paid', $trip) }}" class="inline ml-2">@csrf<button type="submit" class="erp-btn-sm-primary" data-confirm="Mark OT as paid?">Mark Paid</button></form>
@endif</p>
@endif
</div>
@endsection
