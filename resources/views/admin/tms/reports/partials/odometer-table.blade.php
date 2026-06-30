<thead>
    <tr>
        <th>Date</th>
        <th>Vehicle</th>
        <th>Morning</th>
        <th>Evening</th>
        <th>Daily KM</th>
    </tr>
</thead>
<tbody>
    @forelse($rows as $o)
        <tr>
            <td>{{ $o->log_date?->format('d M Y') }}</td>
            <td>{{ $o->vehicle?->displayLabel() }}</td>
            <td>{{ $o->morning_km ?? '—' }}</td>
            <td>{{ $o->evening_km ?? '—' }}</td>
            <td>{{ $o->dailyKm() ?? '—' }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-center py-8 text-gray-400">No records.</td></tr>
    @endforelse
</tbody>
