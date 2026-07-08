<thead>
    <tr>
        <th>Date</th>
        <th>Vehicle</th>
        <th>Vendor</th>
        <th>KM</th>
        <th>Rate</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Paid At</th>
        <th></th>
    </tr>
</thead>
<tbody>
    @forelse($rows as $c)
        <tr>
            <td class="text-xs">{{ $c->log_date?->format('d M Y') ?? $c->created_at?->format('d M Y') }}</td>
            <td class="text-xs">{{ $c->vehicle?->displayLabel() }}</td>
            <td class="text-xs">{{ $c->rentalVendor?->name ?? '—' }}</td>
            <td class="tabular-nums">{{ $c->total_km }}</td>
            <td class="tabular-nums">৳{{ number_format($c->km_rate, 2) }}</td>
            <td class="tabular-nums">৳{{ number_format($c->amount, 2) }}</td>
            <td>{{ $c->payment_status }}</td>
            <td>{{ $c->paid_at?->format('d M Y') ?? '—' }}</td>
            <td class="text-right">
                @if($c->payment_status === 'pending' && auth()->user()->hasPermission('tms.rental_charges.manage'))
                    <form method="POST" action="{{ route('admin.tms.rental-charges.mark-paid', $c) }}" class="inline"
                          data-confirm="Mark as paid?"
                          data-confirm-variant="primary"
                          data-confirm-ok="Yes, mark paid">
                        @csrf
                        <button type="submit" class="erp-btn-sm-primary">Mark Paid</button>
                    </form>
                @elseif($c->payment_status === 'paid' && auth()->user()->hasPermission('tms.rental_charges.manage'))
                    <form method="POST" action="{{ route('admin.tms.rental-charges.unmark-paid', $c) }}" class="inline"
                          data-confirm="Unmark as paid?"
                          data-confirm-variant="warning"
                          data-confirm-ok="Yes, unmark">
                        @csrf
                        <button type="submit" class="erp-btn-sm-secondary">Unmark Paid</button>
                    </form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="9" class="text-center py-8 text-gray-400">No records.</td></tr>
    @endforelse
</tbody>
