@if($histories->isNotEmpty())
    <div class="erp-panel p-6 {{ $class ?? '' }}">
        <h3 class="font-semibold mb-3">Status History</h3>
        <div class="overflow-x-auto">
            <table class="erp-table text-sm">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>From</th>
                        <th>To</th>
                        <th>By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($histories->sortByDesc('created_at') as $history)
                        <tr>
                            <td class="tabular-nums text-xs whitespace-nowrap">@portalDateCommaTime($history->created_at)</td>
                            <td>{{ $history->fromStatusLabel() }}</td>
                            <td>{{ $history->toStatusLabel() }}</td>
                            <td class="text-xs">{{ $history->actorLabel() }}</td>
                            <td class="text-xs text-gray-600">{{ $history->notes ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
