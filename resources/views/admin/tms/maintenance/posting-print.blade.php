<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Bill For Posting — {{ $report['workshop'] }}</title>
    <style>
        @page { margin: 16mm; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; margin: 0; padding: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: none; padding: 8px; vertical-align: middle; }
        thead .title th { text-align: center; padding: 12px 8px; border-bottom: 1px solid #333; }
        thead .columns th {
            background: #f3f4f6;
            font-size: 11px;
            text-transform: uppercase;
            padding: 10px 8px;
            border-bottom: 1px solid #333;
            border-right: 1px solid #333;
        }
        thead .columns th.posting-col-sl { border-left: 1px solid #333; }
        tbody td {
            border-bottom: 1px solid #333;
            border-right: 1px solid #333;
        }
        tbody td.posting-col-sl { border-left: 1px solid #333; }
        .posting-merged { text-align: center; vertical-align: middle; }
        tbody tr:not(:last-child) td.posting-merged:not(.posting-merged-group) { border-bottom: none; }
        tbody tr.posting-group-end td,
        tbody td.posting-merged-group { border-bottom: none; }
        tbody tr.posting-group-separator td {
            height: 0;
            padding: 0;
            line-height: 0;
            border: none;
            border-top: 1px solid #333;
            background: transparent;
        }
        tbody tr.posting-group-separator + tr > td { border-top: none; }
        .report-title { font-size: 18px; font-weight: bold; margin-top: 4px; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .grand-total td { font-weight: bold; background: #f9fafb; border-top: 1px solid #333; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <p class="no-print"><button onclick="window.print()">Print</button></p>

    <table>
        <thead>
            <tr class="title">
                <th colspan="5">
                    <div class="font-bold uppercase">{{ $report['factory_name'] }}</div>
                    <div class="report-title">Bill For Posting</div>
                    <div class="font-bold uppercase">{{ $report['workshop'] }}</div>
                    <div>Date: {{ $report['report_date'] }}</div>
                </th>
            </tr>
            <tr class="columns">
                <th class="posting-col-sl" style="width:40px">SL</th>
                <th>Car No</th>
                <th>User</th>
                <th>Description</th>
                <th style="width:100px">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['groups'] as $group)
                @foreach($group['rows'] as $index => $row)
                    <tr @class(['posting-group-end' => $loop->last])>
                        @if($index === 0)
                            <td rowspan="{{ $group['rowspan'] }}"
                                @class(['posting-col-sl posting-merged', 'posting-merged-group' => $group['rowspan'] > 1])>{{ $group['sl'] }}</td>
                            <td rowspan="{{ $group['rowspan'] }}"
                                @class(['posting-merged', 'posting-merged-group' => $group['rowspan'] > 1])>{{ $group['car_no'] }}</td>
                            <td rowspan="{{ $group['rowspan'] }}"
                                @class(['posting-merged', 'posting-merged-group' => $group['rowspan'] > 1])>{{ $group['user'] }}</td>
                        @endif
                        <td>{{ $row['description'] }}</td>
                        <td class="text-right">{{ number_format($row['amount'], 2) }}</td>
                    </tr>
                @endforeach

                @unless($loop->last)
                    <tr class="posting-group-separator" aria-hidden="true">
                        <td colspan="5"></td>
                    </tr>
                @endunless
            @endforeach

            <tr class="grand-total">
                <td colspan="4" class="text-right">Grand Total</td>
                <td class="text-right">{{ number_format($report['grand_total'], 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
