<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Bill For Posting — {{ $report['workshop'] }}</title>
<style>
@page { margin: 16mm; }
body { font-family: Arial, sans-serif; font-size: 12px; color: #111; margin: 0; padding: 16px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #333; padding: 8px; vertical-align: middle; }
thead .title th { border: none; text-align: center; padding: 12px 8px; }
thead .columns th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; }
.posting-merged { text-align: center; vertical-align: middle; border-left: 1px solid #333; border-right: 1px solid #333; }
tbody tr:first-child .posting-merged { border-top: 1px solid #333; }
tbody tr:last-child td { border-bottom: 1px solid #333; }
tbody tr + tr td.posting-merged { border-top: none; border-bottom: none; }
.text-right { text-align: right; }
.font-bold { font-weight: bold; }
.uppercase { text-transform: uppercase; }
.grand-total td { font-weight: bold; background: #f9fafb; }
@media print { .no-print { display: none; } }
</style>
</head>
<body>
<p class="no-print"><button onclick="window.print()">Print</button></p>
<table>
<thead>
<tr class="title"><th colspan="5">
<div class="font-bold uppercase">{{ $report['factory_name'] }}</div>
<div class="font-bold">Bill For Posting</div>
<div class="font-bold uppercase">{{ $report['workshop'] }}</div>
<div>Date: {{ $report['report_date'] }}</div>
</th></tr>
<tr class="columns">
<th style="width:40px">SL</th><th>Car No</th><th>User</th><th>Description</th><th style="width:100px">Amount</th>
</tr>
</thead>
<tbody>
@foreach($report['groups'] as $group)
@foreach($group['rows'] as $index => $row)
<tr>
@if($index === 0)
<td rowspan="{{ $group['rowspan'] }}" class="posting-merged">{{ $group['sl'] }}</td>
<td rowspan="{{ $group['rowspan'] }}" class="posting-merged">{{ $group['car_no'] }}</td>
<td rowspan="{{ $group['rowspan'] }}" class="posting-merged">{{ $group['user'] }}</td>
@endif
<td>{{ $row['description'] }}</td>
<td class="text-right">{{ number_format($row['amount'], 2) }}</td>
</tr>
@endforeach
@endforeach
<tr class="grand-total">
<td colspan="4" class="text-right">Grand Total</td>
<td class="text-right">{{ number_format($report['grand_total'], 2) }}</td>
</tr>
</tbody>
</table>
</body>
</html>
