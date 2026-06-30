<thead>
    <tr>
        <th>Date</th>
        <th>Vehicle</th>
        <th>Type</th>
        <th>Qty</th>
        <th>Amount</th>
        <th>Paid By</th>
    </tr>
</thead>
<tbody>
    @forelse($rows as $f)
        <tr>
            <td class="text-xs">{{ $f->created_at?->format('d M Y') }}</td>
            <td class="text-xs">{{ $f->vehicle?->displayLabel() }}</td>
            <td>{{ $f->fuel_type }}</td>
            <td>{{ $f->quantity }}</td>
            <td>৳{{ number_format($f->amount, 2) }}</td>
            <td>{{ config('tms.fuel_paid_by.' . $f->paid_by, $f->paid_by) }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center py-8 text-gray-400">No records.</td></tr>
    @endforelse
</tbody>
