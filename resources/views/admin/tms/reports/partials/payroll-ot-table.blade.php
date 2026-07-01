<thead>
    <tr>
        <th>Period</th>
        <th>Duty Date</th>
        <th>Trip</th>
        <th>Employee Code</th>
        <th>Name</th>
        <th>Type</th>
        <th>Vehicle</th>
        <th class="text-right">OT Hrs</th>
        <th class="text-right">OT Hourly</th>
        <th class="text-right">Night</th>
        <th class="text-right">Holiday</th>
        <th class="text-right">Total Pay</th>
        <th>Status</th>
    </tr>
</thead>
<tbody>
    @forelse($rows as $row)
        <tr>
            <td class="tabular-nums">{{ $row->period }}</td>
            <td class="tabular-nums">{{ $row->duty_date }}</td>
            <td><a href="{{ route('admin.tms.trips.show', $row->trip_id) }}" class="text-indigo-600">#{{ $row->trip_id }}</a></td>
            <td class="tabular-nums">{{ $row->employee_code ?: '—' }}</td>
            <td>{{ $row->employee_name }}</td>
            <td>{{ $row->driver_type }}</td>
            <td class="text-xs">{{ $row->vehicle }}</td>
            <td class="text-right tabular-nums">{{ number_format($row->ot_hours, 2) }}</td>
            <td class="text-right tabular-nums">{{ number_format($row->ot_hourly_amount, 2) }}</td>
            <td class="text-right tabular-nums">{{ number_format($row->night_bill_amount, 2) }}</td>
            <td class="text-right tabular-nums">{{ number_format($row->holiday_duty_amount, 2) }}</td>
            <td class="text-right tabular-nums font-medium">{{ number_format($row->total_driver_pay, 2) }}</td>
            <td><span class="erp-badge {{ $row->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">{{ ucfirst($row->payment_status) }}</span></td>
        </tr>
    @empty
        <tr><td colspan="13" class="text-center py-8 text-gray-400">No records.</td></tr>
    @endforelse
</tbody>
