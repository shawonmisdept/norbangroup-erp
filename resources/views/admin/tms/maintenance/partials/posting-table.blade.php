@if($report)
    <div class="erp-panel overflow-hidden maintenance-posting-table">
        <table class="maintenance-posting-grid">
            <thead>
                <tr class="maintenance-posting-title">
                    <th colspan="5">
                        <p class="text-base font-bold uppercase">{{ $report['factory_name'] }}</p>
                        <p class="maintenance-posting-report-title">Bill For Posting</p>
                        <p class="text-sm font-semibold">{{ strtoupper($report['workshop']) }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">Date: {{ $report['report_date'] }}</p>
                    </th>
                </tr>
                <tr class="maintenance-posting-head">
                    <th class="posting-col-sl w-12 text-center">SL</th>
                    <th>Car No</th>
                    <th>User</th>
                    <th>Description</th>
                    <th class="text-right w-32">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['groups'] as $group)
                    @foreach($group['rows'] as $index => $row)
                        <tr @class(['posting-group-end' => $loop->last])>
                            @if($index === 0)
                                <td rowspan="{{ $group['rowspan'] }}"
                                    @class([
                                        'posting-col-sl posting-merged text-center align-middle font-medium',
                                        'posting-merged-group' => $group['rowspan'] > 1,
                                    ])>{{ $group['sl'] }}</td>
                                <td rowspan="{{ $group['rowspan'] }}"
                                    @class([
                                        'posting-merged text-center align-middle text-xs',
                                        'posting-merged-group' => $group['rowspan'] > 1,
                                    ])>{{ $group['car_no'] }}</td>
                                <td rowspan="{{ $group['rowspan'] }}"
                                    @class([
                                        'posting-merged text-center align-middle text-xs',
                                        'posting-merged-group' => $group['rowspan'] > 1,
                                    ])>{{ $group['user'] }}</td>
                            @endif
                            <td class="text-xs {{ ($interactive ?? false) ? 'posting-desc cursor-help' : '' }}"
                                @if($interactive ?? false) title="Bill No: {{ $row['bill_no'] }}&#10;Date: {{ $row['bill_date'] }}" @endif>{{ $row['description'] }}</td>
                            <td class="text-right tabular-nums">৳{{ number_format($row['amount'], 2) }}</td>
                        </tr>
                    @endforeach

                    @unless($loop->last)
                        <tr class="posting-group-separator" aria-hidden="true">
                            <td colspan="5"></td>
                        </tr>
                    @endunless
                @endforeach

                <tr class="maintenance-posting-total">
                    <td colspan="4" class="text-right">Grand Total</td>
                    <td class="text-right tabular-nums">৳{{ number_format($report['grand_total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
@endif
