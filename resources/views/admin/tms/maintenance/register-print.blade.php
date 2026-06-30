<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Summary Of Vehicle Maintenance — {{ $vehicle->displayLabel() }}</title>
    <style>
        @page { margin: 16mm; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; margin: 0; padding: 16px; }
        .header { text-align: center; margin-bottom: 16px; }
        .header .title { font-size: 14px; font-weight: bold; text-transform: uppercase; }
        .header .subtitle { font-size: 12px; margin-top: 4px; }
        .month-bar { display: flex; justify-content: space-between; font-weight: bold; padding: 8px 0; margin-top: 16px; border-bottom: 1px solid #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; table-layout: fixed; }
        col.col-bill-no { width: 8%; }
        col.col-date { width: 10%; }
        col.col-workshop { width: 14%; }
        col.col-item { width: 36%; }
        col.col-qty { width: 8%; }
        col.col-unit { width: 8%; }
        col.col-amount { width: 14%; }
        th, td { border: 1px solid #333; padding: 6px 8px; vertical-align: top; overflow-wrap: anywhere; }
        thead th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; text-align: left; }
        .text-right { text-align: right; }
        .bill-total td { font-weight: bold; background: #f9fafb; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <p class="no-print"><button onclick="window.print()">Print</button></p>

    <div class="header">
        <div class="title">{{ $vehicle->factory?->name ?? 'Company' }}</div>
        <div class="title">Summary Of Vehicle Maintenance</div>
        <div class="subtitle">
            {{ $vehicle->postingCarNoLabel() }}
            @if($vehicle->allocatedUserLabel())
                · {{ $vehicle->allocatedUserLabel() }}
            @endif
        </div>
        <div class="subtitle">{{ $vehicle->displayLabel() }}</div>
        <div>Date: {{ now()->format('d-M-Y') }}</div>
    </div>

    @forelse($monthGroups as $monthKey => $bills)
        @php $monthTotal = $bills->sum('total_amount'); @endphp

        <div class="month-bar">
            <span>Month Of: {{ $bills->first()?->monthLabel() }}</span>
            <span>Sub Total: ৳{{ number_format($monthTotal, 2) }}</span>
        </div>

        <table>
            <colgroup>
                <col class="col-bill-no">
                <col class="col-date">
                <col class="col-workshop">
                <col class="col-item">
                <col class="col-qty">
                <col class="col-unit">
                <col class="col-amount">
            </colgroup>

            <thead>
                <tr>
                    <th>Bill No</th>
                    <th>Date</th>
                    <th>Workshop</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>

            <tbody>
                @foreach($bills as $bill)
                    @foreach($bill->items as $index => $item)
                        <tr>
                            @if($index === 0)
                                <td rowspan="{{ $bill->items->count() }}">{{ $bill->bill_no }}</td>
                                <td rowspan="{{ $bill->items->count() }}">{{ $bill->bill_date?->format('d M Y') }}</td>
                                <td rowspan="{{ $bill->items->count() }}">{{ $bill->workshop_name }}</td>
                            @endif

                            <td>{{ $item->item_name }}</td>
                            <td class="text-right">{{ $item->formattedQuantity() ?? '—' }}</td>
                            <td>{{ $item->unit ?: '—' }}</td>
                            <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach

                    <tr class="bill-total">
                        <td colspan="6" class="text-right">Bill Total</td>
                        <td class="text-right">{{ number_format($bill->total_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p>No maintenance bills yet.</p>
    @endforelse
</body>
</html>
