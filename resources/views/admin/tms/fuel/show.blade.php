@extends('layouts.admin')
@section('title', 'Fuel Entry #' . $fuelLog->id)
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Fuel Entry #' . $fuelLog->id,
    'subtitle' => $fuelLog->vehicle?->displayLabel() ?? 'Fuel log details',
    'actions' => '<a href="' . route('admin.tms.fuel.index') . '" class="erp-btn-secondary">← Back</a>'
        . ($canManage ? ' <a href="' . route('admin.tms.fuel.edit', $fuelLog) . '" class="erp-btn-primary !py-2 !px-4 text-xs">Edit</a>' : ''),
])

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 erp-panel p-6 space-y-3 text-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Unit</span>
                <span class="font-medium">{{ $fuelLog->factory?->name ?? '—' }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Date</span>
                <span class="font-medium tabular-nums">{{ $fuelLog->created_at?->format('d M Y, H:i') ?? '—' }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Vehicle</span>
                @if($fuelLog->vehicle)
                    <a href="{{ route('admin.tms.vehicles.show', $fuelLog->vehicle) }}" class="font-medium text-indigo-600">{{ $fuelLog->vehicle->displayLabel() }}</a>
                @else
                    <span class="font-medium">—</span>
                @endif
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Fuel Type</span>
                <span class="font-medium capitalize">{{ $fuelLog->fuel_type }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Quantity</span>
                <span class="font-medium tabular-nums">{{ number_format((float) $fuelLog->quantity, 3) }} {{ $fuelLog->unit }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Unit Price</span>
                <span class="font-medium tabular-nums">৳{{ number_format((float) $fuelLog->unit_price, 2) }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Amount</span>
                <span class="font-medium tabular-nums">৳{{ number_format((float) $fuelLog->amount, 2) }}</span>
            </div>
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Paid By</span>
                <span class="font-medium capitalize">{{ str_replace('_', ' ', $fuelLog->paid_by) }}</span>
            </div>
            @if($fuelLog->receipt_number)
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Receipt No</span>
                    <span class="font-medium tabular-nums">{{ $fuelLog->receipt_number }}</span>
                </div>
            @endif
            @if($fuelLog->trip_log_id)
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Trip</span>
                    <a href="{{ route('admin.tms.trips.show', $fuelLog->trip_log_id) }}" class="font-medium text-indigo-600">Trip #{{ $fuelLog->trip_log_id }}</a>
                    @if($fuelLog->tripLog?->transportRequest?->employee)
                        <span class="block text-xs text-gray-500 mt-0.5">{{ $fuelLog->tripLog->transportRequest->employee->name }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="erp-panel p-6">
        <h3 class="font-semibold mb-3 text-sm">Receipt</h3>

        @if($fuelLog->hasReceipt())
            <div class="flex flex-col items-start gap-3">
                <div class="w-[200px] h-[200px] rounded-lg border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center shrink-0">
                    @if($fuelLog->receiptIsImage())
                        <img
                            src="{{ $fuelLog->receiptUrl() }}"
                            alt="Fuel receipt"
                            class="w-[200px] h-[200px] object-cover"
                        >
                    @elseif($fuelLog->receiptIsPdf())
                        <iframe
                            src="{{ $fuelLog->receiptUrl() }}#toolbar=0"
                            title="Fuel receipt PDF"
                            class="w-[200px] h-[200px] border-0 bg-white"
                        ></iframe>
                    @else
                        <span class="text-xs text-gray-400 px-2 text-center">Preview unavailable</span>
                    @endif
                </div>

                <a href="{{ route('admin.tms.fuel.receipt', $fuelLog) }}" class="erp-btn-primary">
                    Download Receipt
                </a>
            </div>
        @else
            <p class="text-sm text-gray-400">No receipt uploaded for this entry.</p>
        @endif
    </div>
</div>
@endsection
