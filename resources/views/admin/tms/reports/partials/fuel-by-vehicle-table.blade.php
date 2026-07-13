<thead>
    <tr>
        <th>Vehicle</th>
        <th>Entries</th>
        <th>Total Qty</th>
        <th>Total Amount</th>
        <th>Company</th>
        <th>Rental Party</th>
        <th></th>
    </tr>
</thead>
<tbody>
    @forelse($rows as $row)
        <tr>
            <td class="text-xs font-medium">{{ $row->vehicle?->displayLabel() ?? '—' }}</td>
            <td class="tabular-nums">{{ $row->entry_count }}</td>
            <td class="tabular-nums">@portalQuantity($row->total_quantity)</td>
            <td class="tabular-nums">৳{{ number_format((float) $row->total_amount, 2) }}</td>
            <td class="tabular-nums text-xs">৳{{ number_format((float) $row->company_amount, 2) }}</td>
            <td class="tabular-nums text-xs">৳{{ number_format((float) $row->rental_party_amount, 2) }}</td>
            <td class="text-right">
                @if($row->vehicle_id)
                    <a href="{{ route('admin.tms.reports.index', array_merge($filters, ['tab' => 'fuel', 'fuel_view' => 'detail', 'vehicle_id' => $row->vehicle_id])) }}"
                       class="erp-btn-sm-secondary">View entries</a>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center py-8 text-gray-400">No records.</td></tr>
    @endforelse
</tbody>
