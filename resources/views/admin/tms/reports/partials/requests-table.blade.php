<thead>
    <tr>
        <th>ID</th>
        <th>Employee</th>
        <th>Destination</th>
        <th>When</th>
        <th>Pax</th>
        <th>Status</th>
        <th>Trip</th>
        <th>Driver</th>
        <th></th>
    </tr>
</thead>
<tbody>
    @forelse($rows as $r)
        <tr>
            <td>#{{ $r->id }}</td>
            <td>{{ $r->employee?->name }}</td>
            <td class="text-xs">{{ $r->destinationLabel() }}</td>
            <td>@include('partials.erp.datetime-highlight', ['at' => $r->pickup_at, 'variant' => 'admin'])</td>
            <td>{{ $r->passenger_count }}</td>
            <td><span class="erp-badge {{ $r->statusBadgeClass() }}">{{ $r->statusLabel() }}</span></td>
            <td>
                @if($r->trip_log_id)
                    <a href="{{ route('admin.tms.trips.show', $r->trip_log_id) }}" class="erp-btn-sm-secondary">#{{ $r->trip_log_id }}</a>
                @else
                    —
                @endif
            </td>
            <td class="text-xs">{{ $r->assignedDriverLabel() }}</td>
            <td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.tms.requests.show', $r)])</td>
        </tr>
    @empty
        <tr><td colspan="9" class="text-center py-8 text-gray-400">No records.</td></tr>
    @endforelse
</tbody>
