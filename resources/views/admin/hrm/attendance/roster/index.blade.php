@extends('layouts.admin')
@section('title', 'Shift Roster')
@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'roster'])
@include('partials.erp.page-header', [
    'title' => 'Shift Roster',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.attendance.roster.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs mr-2">New Roster</a>' : '')
        . '<a href="' . route('admin.hrm.attendance.roster.variance') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Variance Report</a>',
])
<div class="erp-panel"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Factory</th><th>Period</th><th>Assignments</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rosters as $roster)
<tr><td>{{ $roster->factory?->name }}</td>
<td>{{ $roster->start_date->format('d M') }} – {{ $roster->end_date->format('d M Y') }}</td>
<td>{{ $roster->entries_count }}</td>
<td>{{ ucfirst($roster->status) }}</td>
<td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.hrm.attendance.roster.show', $roster), 'viewLabel' => 'Open'])</td></tr>
@empty<tr><td colspan="5" class="text-center py-8 text-gray-400">No rosters yet.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $rosters->links() }}</div></div>
@endsection
