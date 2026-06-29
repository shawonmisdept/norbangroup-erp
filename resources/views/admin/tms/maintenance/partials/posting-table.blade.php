@if($report)
<div class="erp-panel overflow-hidden maintenance-posting-table">
<table class="maintenance-posting-grid">
<thead>
<tr class="maintenance-posting-title">
<th colspan="5">
<p class="text-base font-bold uppercase">{{ $report['factory_name'] }}</p>
<p class="text-sm font-semibold mt-0.5">Bill For Posting</p>
<p class="text-sm font-semibold">{{ strtoupper($report['workshop']) }}</p>
<p class="text-xs text-gray-500 mt-0.5">Date: {{ $report['report_date'] }}</p>
</th>
</tr>
<tr class="maintenance-posting-head">
<th class="w-12 text-center">SL</th>
<th>Car No</th>
<th>User</th>
<th>Description</th>
<th class="text-right w-32">Amount</th>
</tr>
</thead>
<tbody>
@foreach($report['groups'] as $group)
@foreach($group['rows'] as $index => $row)
<tr>
@if($index === 0)
<td rowspan="{{ $group['rowspan'] }}" class="posting-merged text-center align-middle font-medium">{{ $group['sl'] }}</td>
<td rowspan="{{ $group['rowspan'] }}" class="posting-merged text-center align-middle text-xs">{{ $group['car_no'] }}</td>
<td rowspan="{{ $group['rowspan'] }}" class="posting-merged text-center align-middle text-xs">{{ $group['user'] }}</td>
@endif
<td class="text-xs {{ ($interactive ?? false) ? 'posting-desc cursor-help' : '' }}"
    @if($interactive ?? false) title="Bill No: {{ $row['bill_no'] }}&#10;Date: {{ $row['bill_date'] }}" @endif>{{ $row['description'] }}</td>
<td class="text-right tabular-nums">৳{{ number_format($row['amount'], 2) }}</td>
</tr>
@endforeach
@endforeach
<tr class="maintenance-posting-total">
<td colspan="4" class="text-right">Grand Total</td>
<td class="text-right tabular-nums">৳{{ number_format($report['grand_total'], 2) }}</td>
</tr>
</tbody>
</table>
</div>
@endif
