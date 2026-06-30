<thead>
    <tr>
        <th>ID</th>
        <th>Employees</th>
        <th>Pax</th>
        <th>Vehicle</th>
        <th>Driver</th>
        <th>KM</th>
        <th>Duty End</th>
        <th>Driver Pay</th>
        <th>Rental</th>
        <th>Status</th>
        <th></th>
    </tr>
</thead>
<tbody>
    @forelse($rows as $t)
        <tr>
            <td class="tabular-nums">#{{ $t->id }}</td>
            <td class="text-xs">{{ $t->transportRequests->pluck('employee.name')->filter()->implode(', ') }}</td>
            <td>{{ $t->total_passengers }}</td>
            <td class="text-xs">{{ $t->vehicle?->displayLabel() }}</td>
            <td class="text-xs">{{ $t->assignedDriverLabel() }}</td>
            <td class="tabular-nums">{{ $t->total_km ?? '—' }}</td>
            <td>@include('partials.erp.datetime-highlight', ['at' => $t->duty_end_at, 'variant' => 'admin'])</td>
            <td class="tabular-nums">৳{{ number_format($t->total_driver_pay ?: $t->ot_amount, 2) }}</td>
            <td class="tabular-nums">
                @if($t->rental_charge_amount)
                    ৳{{ number_format($t->rental_charge_amount, 2) }}
                @else
                    —
                @endif
            </td>
            <td><span class="erp-badge {{ $t->tripStatusBadgeClass() }}">{{ $t->tripStatusLabel() }}</span></td>
            <td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.tms.trips.show', $t)])</td>
        </tr>
    @empty
        <tr><td colspan="11" class="text-center py-8 text-gray-400">No records.</td></tr>
    @endforelse
</tbody>
