@extends('layouts.admin')
@section('title', 'Roster Variance')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Roster vs Attendance Variance',
    'subtitle' => 'Shift mismatches and missing attendance on published rosters',
    'actions' => '<a href="' . route('admin.hrm.attendance.roster.index') . '" class="erp-btn-secondary">← Rosters</a>',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.attendance.roster.variance') }}" class="erp-filter-bar">
            <div class="erp-filter-field">
                <label class="erp-form-label">Factory</label>
                <select name="factory_id" class="erp-input !text-xs" required onchange="this.form.submit()">
                    <option value="">Select</option>
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" {{ $filterFactoryId === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @if($filterFactoryId)
            <div class="erp-filter-field-grow">
                <label class="erp-form-label">Published Roster</label>
                <select name="roster_id" class="erp-input !text-xs" onchange="this.form.submit()">
                    <option value="">All published rosters</option>
                    @foreach($rosters as $roster)
                        <option value="{{ $roster->id }}" {{ $filterRosterId === (string) $roster->id ? 'selected' : '' }}>
                            {{ $roster->start_date->format('d M') }} – {{ $roster->end_date->format('d M Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('admin.hrm.attendance.roster.variance.export', array_filter(['factory_id' => $filterFactoryId, 'roster_id' => $filterRosterId ?: null])) }}"
               class="erp-btn-primary !py-1.5 !px-3 text-xs self-end">Export CSV</a>
            @endif
        </form>
    </div>
</div>

@if($filterFactoryId)
<div class="erp-panel overflow-hidden">
    <table class="erp-table w-full text-xs">
        <thead>
            <tr>
                <th>Date</th>
                <th>Employee</th>
                <th>Roster Shift</th>
                <th>Actual Shift</th>
                <th>Attendance</th>
                <th>Variance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row['roster_date'])->format('D, d M Y') }}</td>
                    <td>
                        <span class="font-mono text-[10px] text-gray-400">{{ $row['employee']?->employee_code }}</span>
                        <span class="block">{{ $row['employee']?->name }}</span>
                    </td>
                    <td>{{ $row['roster_shift'] }}</td>
                    <td>{{ $row['actual_shift'] ?? '—' }}</td>
                    <td>{{ $row['attendance_status'] ? ucfirst(str_replace('_', ' ', $row['attendance_status'])) : '—' }}</td>
                    <td>
                        <span class="erp-badge {{ $row['variance_type'] === 'shift_mismatch' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800' }}">
                            {{ $row['variance_type'] === 'shift_mismatch' ? 'Shift mismatch' : 'No attendance' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-8 text-gray-400">No variances found for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@else
    <div class="erp-panel"><div class="erp-panel-body text-center text-gray-400 py-8">Select a factory to view variance report.</div></div>
@endif
@endsection
