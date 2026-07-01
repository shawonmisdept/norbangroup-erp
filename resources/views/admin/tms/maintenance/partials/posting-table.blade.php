@if($report)
    @php
        $showBulk = ($interactive ?? false) && auth()->user()->canManageTmsSubmodule('maintenance');
        $colspan = $showBulk ? 7 : 6;
        $unpostedIds = collect($report['groups'])->flatMap(fn ($g) => $g['rows'])->filter(fn ($r) => ! ($r['posted'] ?? false))->pluck('id')->filter()->values();
    @endphp

    @if($showBulk && $unpostedIds->isNotEmpty())
        <form method="POST" action="{{ route('admin.tms.maintenance.posting.bulk-post') }}" class="erp-panel p-4 mb-4 space-y-3" id="bulk-post-form">
            @csrf
            @foreach(request()->only(['factory_id', 'workshop', 'from', 'to', 'unposted_only']) as $key => $value)
                @if($value !== null && $value !== '')
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <p class="text-sm text-gray-600">Select unposted bills below, or select all {{ $unpostedIds->count() }} unposted bill(s) in this report.</p>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="erp-btn-primary" data-confirm="Mark selected bills as posted to finance?">Post Selected to Finance</button>
                <button type="button" class="erp-btn-secondary" onclick="document.querySelectorAll('.bulk-post-checkbox').forEach(c => c.checked = true)">Select All Unposted</button>
            </div>
        </form>
    @endif

    <div class="erp-panel overflow-hidden maintenance-posting-table">
        <table class="maintenance-posting-grid">
            <thead>
                <tr class="maintenance-posting-title">
                    <th colspan="{{ $colspan }}">
                        <p class="text-base font-bold uppercase">{{ $report['factory_name'] }}</p>
                        <p class="maintenance-posting-report-title">Bill For Posting</p>
                        <p class="text-sm font-semibold">{{ strtoupper($report['workshop']) }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">Date: {{ $report['report_date'] }}</p>
                    </th>
                </tr>
                <tr class="maintenance-posting-head">
                    @if($showBulk)
                        <th class="w-10"></th>
                    @endif
                    <th class="posting-col-sl w-12 text-center">SL</th>
                    <th>Car No</th>
                    <th>User</th>
                    <th>Description</th>
                    <th>Finance</th>
                    <th class="text-right w-32">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['groups'] as $group)
                    @foreach($group['rows'] as $index => $row)
                        <tr @class(['posting-group-end' => $loop->last])>
                            @if($showBulk)
                                <td class="text-center align-middle">
                                    @if(! ($row['posted'] ?? false) && ! empty($row['id']))
                                        <input type="checkbox" form="bulk-post-form" name="bill_ids[]" value="{{ $row['id'] }}" class="bulk-post-checkbox rounded border-gray-300">
                                    @endif
                                </td>
                            @endif

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
                            <td class="text-xs">
                                @if($row['posted'] ?? false)
                                    <span class="erp-badge bg-green-100 text-green-800">Posted</span>
                                    @if(! empty($row['posted_at']))
                                        <span class="block text-[10px] text-gray-500 mt-0.5">{{ $row['posted_at'] }}</span>
                                    @endif
                                @else
                                    <span class="erp-badge bg-amber-100 text-amber-800">Unposted</span>
                                @endif
                            </td>
                            <td class="text-right tabular-nums">৳{{ number_format($row['amount'], 2) }}</td>
                        </tr>
                    @endforeach

                    @unless($loop->last)
                        <tr class="posting-group-separator" aria-hidden="true">
                            <td colspan="{{ $colspan }}"></td>
                        </tr>
                    @endunless
                @endforeach

                <tr class="maintenance-posting-total">
                    <td colspan="{{ $colspan - 1 }}" class="text-right">Grand Total</td>
                    <td class="text-right tabular-nums">৳{{ number_format($report['grand_total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
@endif
