@extends('layouts.admin')
@section('title', 'Maintenance Register')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Summary Of Vehicle Maintenance',
    'subtitle' => $vehicle->postingCarNoLabel() . ($vehicle->allocatedUserLabel() ? ' · ' . $vehicle->allocatedUserLabel() : ''),
    'actions' => '<a href="' . route('admin.tms.maintenance.index') . '" class="erp-btn-secondary">← Vehicles</a>'
        . ' <a href="' . $printUrl . '" target="_blank" class="erp-btn-secondary">Print</a>'
        . (auth()->user()->canManageTmsSubmodule('maintenance')
            ? ' <a href="' . route('admin.tms.maintenance.bills.create', $vehicle) . '" class="erp-btn-primary">Add Bill</a>'
            : ''),
])

<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
    <div>
        <label class="erp-label">Bill No</label>
        <input type="text" name="bill_no" class="erp-input" value="{{ $filters['bill_no'] ?? '' }}" placeholder="Search bill no…">
    </div>

    <div>
        <label class="erp-label">From</label>
        <input type="date" name="from" class="erp-input" value="{{ $filters['from'] ?? '' }}">
    </div>

    <div>
        <label class="erp-label">To</label>
        <input type="date" name="to" class="erp-input" value="{{ $filters['to'] ?? '' }}">
    </div>

    <div>
        <label class="erp-label">Workshop</label>
        <input type="text" name="workshop" list="register-workshop-list" class="erp-input" value="{{ $filters['workshop'] ?? '' }}" placeholder="JK Motors">
        <datalist id="register-workshop-list">
            @foreach($workshops as $w)
                <option value="{{ $w }}">
            @endforeach
        </datalist>
    </div>

    <div>
        <label class="erp-label">Item</label>
        <input type="text" name="item" list="register-item-list" class="erp-input" value="{{ $filters['item'] ?? '' }}" placeholder="Spark Plug">
        <datalist id="register-item-list">
            @foreach($items as $i)
                <option value="{{ $i }}">
            @endforeach
        </datalist>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="erp-btn-primary flex-1">Filter</button>
        @if(array_filter($filters ?? []))
            <a href="{{ route('admin.tms.maintenance.register', $vehicle) }}" class="erp-btn-secondary">Clear</a>
        @endif
    </div>
</form>

@php $canManageBills = auth()->user()->canManageTmsSubmodule('maintenance'); @endphp

<div class="space-y-4">
    @forelse($monthGroups as $monthKey => $bills)
        @php $monthTotal = $bills->sum('total_amount'); @endphp

        <div class="erp-panel overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
                <p class="text-sm font-semibold">Month Of: {{ $bills->first()?->monthLabel() }}</p>
                <p class="text-sm font-semibold tabular-nums">Sub Total: ৳{{ number_format($monthTotal, 2) }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="maintenance-bills-table {{ $canManageBills ? 'has-actions' : '' }}">
                    @include('admin.tms.maintenance.partials.bill-table-cols', ['withActions' => $canManageBills])

                    <thead>
                        <tr>
                            <th>Bill No</th>
                            <th>Date</th>
                            <th>Workshop</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th class="text-center">Amount</th>
                            @if($canManageBills)
                                <th class="text-center"></th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($bills as $bill)
                            @foreach($bill->items as $index => $item)
                                <tr>
                                    @if($index === 0)
                                        <td rowspan="{{ $bill->items->count() }}" class="align-top font-medium text-center">{{ $bill->bill_no }}</td>
                                        <td rowspan="{{ $bill->items->count() }}" class="align-top text-xs whitespace-nowrap text-center">{{ $bill->bill_date?->format('d M Y') }}</td>
                                        <td rowspan="{{ $bill->items->count() }}" class="align-top text-xs text-center">{{ $bill->workshop_name }}</td>
                                    @endif

                                    <td class="text-xs">{{ $item->item_name }}</td>
                                    <td class="text-xs tabular-nums text-center">{{ $item->formattedQuantity() ?? '—' }}</td>
                                    <td class="text-xs tabular-nums text-center">{{ $item->unit ?: '—' }}</td>
                                    <td class="text-right tabular-nums">৳{{ number_format($item->amount, 2) }}</td>

                                    @if($index === 0 && $canManageBills)
                                        <td rowspan="{{ $bill->items->count() }}" class="align-top text-right whitespace-nowrap">
                                            @include('admin.tms.partials.row-actions', [
                                                'editUrl' => route('admin.tms.maintenance.bills.edit', $bill),
                                                'destroyUrl' => route('admin.tms.maintenance.bills.destroy', $bill),
                                                'confirm' => 'Delete this bill?',
                                            ])
                                        </td>
                                    @endif
                                </tr>
                            @endforeach

                            <tr class="bg-gray-50">
                                <td colspan="6" class="text-right text-xs font-semibold uppercase tracking-wide">Bill Total</td>
                                <td class="text-right tabular-nums font-semibold">৳{{ number_format($bill->total_amount, 2) }}</td>
                                @if($canManageBills)
                                    <td></td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="erp-panel p-8 text-center text-gray-400">
            @if(array_filter($filters ?? []))
                No bills match your filters.
            @else
                No maintenance bills yet.
            @endif
        </div>
    @endforelse
</div>
@endsection
