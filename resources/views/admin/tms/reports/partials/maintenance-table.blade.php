<thead>
    <tr>
        <th>Bill No</th>
        <th>Date</th>
        <th>Vehicle</th>
        <th>Workshop</th>
        <th>Total</th>
        <th>Paid By</th>
    </tr>
</thead>
<tbody>
    @forelse($rows as $m)
        <tr>
            <td class="text-xs font-medium">{{ $m->bill_no }}</td>
            <td class="text-xs">{{ $m->bill_date?->format('d M Y') }}</td>
            <td class="text-xs">{{ $m->vehicle?->displayLabel() }}</td>
            <td class="text-xs">{{ $m->workshop_name }}</td>
            <td class="tabular-nums font-medium">৳{{ number_format($m->total_amount, 2) }}</td>
            <td class="text-xs">{{ config('tms.fuel_paid_by.' . $m->paid_by, $m->paid_by) }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center py-8 text-gray-400">No records.</td></tr>
    @endforelse
</tbody>
