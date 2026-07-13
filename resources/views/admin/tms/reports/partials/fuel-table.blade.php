<thead>
    <tr>
        <th>Date</th>
        <th>Vehicle</th>
        <th>Trip</th>
        <th>Type</th>
        <th>Qty</th>
        <th>Amount</th>
        <th>Paid By</th>
    </tr>
</thead>
<tbody>
    @forelse($rows as $f)
        <tr>
            <td class="text-xs tabular-nums">{{ $f->created_at?->format('d M Y') }}</td>
            <td class="text-xs">{{ $f->vehicle?->displayLabel() }}</td>
            <td class="text-xs">
                @if($f->trip_log_id)
                    <a href="{{ route('admin.tms.trips.show', $f->trip_log_id) }}" class="text-indigo-600 hover:underline">#{{ $f->trip_log_id }}</a>
                @else
                    <span class="text-gray-400">—</span>
                @endif
            </td>
            <td class="text-xs">{{ config('tms.fuel_types.' . $f->fuel_type, $f->fuel_type) }}</td>
            <td class="tabular-nums text-xs">@portalQuantity($f->quantity) {{ $f->unit }}</td>
            <td class="tabular-nums">৳{{ number_format($f->amount, 2) }}</td>
            <td class="text-xs">{{ config('tms.fuel_paid_by.' . $f->paid_by, $f->paid_by) }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center py-8 text-gray-400">No records.</td></tr>
    @endforelse
</tbody>
