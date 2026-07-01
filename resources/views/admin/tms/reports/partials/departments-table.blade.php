<thead>
    <tr>
        <th>Department</th>
        <th class="text-right">Requests</th>
        <th class="text-right">Passengers</th>
        <th class="text-right">Pending</th>
        <th class="text-right">Approved</th>
        <th class="text-right">In Progress</th>
        <th class="text-right">Completed</th>
        <th class="text-right">Cancelled</th>
        <th class="text-right">Rejected</th>
    </tr>
</thead>
<tbody>
    @forelse($rows as $row)
        <tr>
            <td class="font-medium">{{ $row->department_name }}</td>
            <td class="text-right tabular-nums">{{ $row->request_count }}</td>
            <td class="text-right tabular-nums">{{ $row->passenger_count }}</td>
            <td class="text-right tabular-nums">{{ $row->pending_count }}</td>
            <td class="text-right tabular-nums">{{ $row->approved_count }}</td>
            <td class="text-right tabular-nums">{{ $row->in_progress_count }}</td>
            <td class="text-right tabular-nums">{{ $row->completed_count }}</td>
            <td class="text-right tabular-nums">{{ $row->cancelled_count }}</td>
            <td class="text-right tabular-nums">{{ $row->rejected_count }}</td>
        </tr>
    @empty
        <tr><td colspan="9" class="text-center py-8 text-gray-400">No records.</td></tr>
    @endforelse
</tbody>
