<thead>
    <tr>
        <th>Trip</th>
        <th>Driver</th>
        <th>Type</th>
        <th>Night</th>
        <th>Holiday</th>
        <th>OT Hrs</th>
        <th>OT Hourly</th>
        <th>Total</th>
        <th>Status</th>
        <th>Paid At</th>
    </tr>
</thead>
<tbody>
    @forelse($rows as $p)
        @php
            $trip = $p->tripLog;
            $bd = $p->payment_breakdown ?? [];
        @endphp
        <tr>
            <td>#{{ $p->trip_log_id }}</td>
            <td class="text-xs">{{ $trip?->assignedDriverLabel() ?? $p->driver?->displayLabel() ?? '—' }}</td>
            <td class="text-xs">{{ $trip?->driver_type ?? '—' }}</td>
            <td class="tabular-nums">৳{{ number_format($bd['night_bill_amount'] ?? $trip?->night_bill_amount ?? 0, 2) }}</td>
            <td class="tabular-nums">৳{{ number_format($bd['holiday_duty_amount'] ?? $trip?->holiday_duty_amount ?? 0, 2) }}</td>
            <td class="tabular-nums">{{ $bd['ot_hours'] ?? $trip?->ot_hours ?? '—' }}</td>
            <td class="tabular-nums">৳{{ number_format($bd['ot_hourly_amount'] ?? $trip?->ot_hourly_amount ?? 0, 2) }}</td>
            <td class="tabular-nums font-medium">৳{{ number_format($p->amount, 2) }}</td>
            <td>{{ $p->payment_status }}</td>
            <td>{{ $p->paid_at?->format('d M Y') ?? '—' }}</td>
        </tr>
    @empty
        <tr><td colspan="10" class="text-center py-8 text-gray-400">No records.</td></tr>
    @endforelse
</tbody>
