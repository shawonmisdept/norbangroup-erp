@extends('layouts.admin')
@section('title', 'Roster')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Roster: ' . $roster->start_date->format('d M') . ' – ' . $roster->end_date->format('d M Y'),
    'subtitle' => ucfirst($roster->status) . ' — ' . $roster->entries->count() . ' assignment(s)',
    'actions' => '<a href="' . route('admin.hrm.attendance.roster.index') . '" class="erp-btn-secondary">← Back</a>',
])
@if($canManage && $roster->status === 'draft')
<form method="POST" action="{{ route('admin.hrm.attendance.roster.publish', $roster) }}" class="mb-4 text-right">@csrf
    <button type="submit" class="erp-btn-primary !text-xs">Publish Roster</button>
</form>
@endif

@if($canManage && $roster->status === 'draft')
<div class="erp-panel mb-4">
    <div class="erp-panel-head flex-wrap gap-2">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-600">Bulk Import (Excel / CSV)</h2>
        <a href="{{ route('admin.hrm.attendance.roster.import-template', $roster) }}" class="erp-btn-secondary !py-1.5 !px-3 text-xs">Download Template</a>
    </div>
    <form method="POST" action="{{ route('admin.hrm.attendance.roster.import', $roster) }}" enctype="multipart/form-data" class="erp-panel-body flex flex-wrap gap-3 items-end">
        @csrf
        <div class="erp-filter-field-grow">
            <label class="erp-form-label">CSV file</label>
            <input type="file" name="file" accept=".csv,.txt" class="erp-input !text-xs" required>
        </div>
        <button type="submit" class="erp-btn-primary !text-xs">Import Assignments</button>
        <p class="w-full text-[11px] text-gray-400">Open template in Excel, fill employee_code / roster_date / shift_code / line_code, save as CSV.</p>
    </form>
</div>
@endif

<div class="erp-panel mb-4 overflow-hidden">
    <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase tracking-wide text-gray-600">Calendar Grid</h2></div>
    <div class="overflow-x-auto">
        <table class="erp-table w-full text-[10px] min-w-max">
            <thead>
                <tr>
                    <th class="sticky left-0 bg-white z-10 min-w-[140px]">Employee</th>
                    @foreach($dates as $date)
                        <th class="text-center min-w-[72px]">{{ \Carbon\Carbon::parse($date)->format('D') }}<br>{{ \Carbon\Carbon::parse($date)->format('d/m') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $emp)
                    <tr>
                        <td class="sticky left-0 bg-white z-10 font-medium whitespace-nowrap">
                            <span class="font-mono text-gray-400">{{ $emp->employee_code }}</span>
                            <span class="block text-xs">{{ $emp->name }}</span>
                        </td>
                        @foreach($dates as $date)
                            @php
                                $key = $emp->id . '|' . $date;
                                $entry = $entryMap->get($key);
                            @endphp
                            <td class="text-center p-1 align-top">
                                @if($canManage && $roster->status === 'draft')
                                    <form method="POST" action="{{ route('admin.hrm.attendance.roster.assign', $roster) }}" class="space-y-0.5">
                                        @csrf
                                        <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                                        <input type="hidden" name="roster_date" value="{{ $date }}">
                                        <select name="shift_id" class="erp-input !text-[9px] !py-0.5 !px-1 w-full" onchange="this.form.submit()" required>
                                            <option value="">—</option>
                                            @foreach($shifts as $s)
                                                <option value="{{ $s->id }}" {{ $entry?->shift_id === $s->id ? 'selected' : '' }}>{{ $s->code }}</option>
                                            @endforeach
                                        </select>
                                        <select name="line_id" class="erp-input !text-[9px] !py-0.5 !px-1 w-full" onchange="if(this.form.shift_id.value) this.form.submit()">
                                            <option value="">Ln</option>
                                            @foreach($lines as $l)
                                                <option value="{{ $l->id }}" {{ $entry?->line_id === $l->id ? 'selected' : '' }}>{{ $l->code }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @elseif($entry)
                                    <span class="inline-block px-1 py-0.5 rounded bg-brand/10 text-brand font-semibold">{{ $entry->shift?->code }}</span>
                                    @if($entry->line)
                                        <span class="block text-gray-400 text-[9px]">{{ $entry->line->code }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($canManage && $roster->status === 'draft')
<div class="erp-panel mb-4"><div class="erp-panel-body">
<form method="POST" action="{{ route('admin.hrm.attendance.roster.assign', $roster) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">@csrf
    <div><label class="erp-form-label">Employee</label><select name="employee_id" class="erp-input !text-xs" required>@foreach($employees as $emp)<option value="{{ $emp->id }}">{{ $emp->name }}</option>@endforeach</select></div>
    <div><label class="erp-form-label">Date</label><select name="roster_date" class="erp-input !text-xs" required>@foreach($dates as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach</select></div>
    <div><label class="erp-form-label">Shift</label><select name="shift_id" class="erp-input !text-xs" required>@foreach($shifts as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
    <div><label class="erp-form-label">Line</label><select name="line_id" class="erp-input !text-xs"><option value="">—</option>@foreach($lines as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach</select></div>
    <button type="submit" class="erp-btn-primary">Assign</button>
</form></div></div>
@endif

<div class="erp-panel"><div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase text-gray-600">Assignment List</h2></div>
<div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Date</th><th>Employee</th><th>Shift</th><th>Line</th></tr></thead>
<tbody>@forelse($roster->entries as $entry)
<tr><td>{{ $entry->roster_date->format('D, d M') }}</td><td>{{ $entry->employee?->name }}</td>
<td>{{ $entry->shift?->name }}</td><td>{{ $entry->line?->name ?? '—' }}</td></tr>
@empty<tr><td colspan="4" class="text-center py-8 text-gray-400">No assignments yet.</td></tr>@endforelse</tbody></table></div></div>
@endsection
