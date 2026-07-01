<thead>
    <tr>
        <th>Department</th>
        <th class="text-right">Completed Trips</th>
        <th class="text-right">Passengers</th>
        <th class="text-right">Driver Pay (৳)</th>
        <th class="text-right">OT Hours</th>
    </tr>
</thead>
<tbody>
    @forelse($rows as $row)
        <tr>
            <td class="font-medium">{{ $row->department_name }}</td>
            <td class="text-right tabular-nums">{{ $row->trip_count }}</td>
            <td class="text-right tabular-nums">{{ $row->passenger_count }}</td>
            <td class="text-right tabular-nums">{{ number_format((float) $row->driver_pay_total, 2) }}</td>
            <td class="text-right tabular-nums">{{ number_format((float) $row->ot_hours_total, 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-center py-8 text-gray-400">No records.</td></tr>
    @endforelse
</tbody>
