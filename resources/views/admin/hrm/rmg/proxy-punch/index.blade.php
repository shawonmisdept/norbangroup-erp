@extends('layouts.admin')
@section('title', 'Proxy Punch Flags')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Proxy Punch Flags',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.rmg.proxy-punch.create') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Flag Punch</a>' : '')
        . '<a href="' . route('admin.hrm.rmg.hub') . '" class="erp-btn-secondary ml-2">← Hub</a>',
])
<div class="erp-panel"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Employee</th><th>Punch</th><th>Reason</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($flags as $flag)
<tr>
<td>{{ $flag->employee?->name ?? '—' }}</td>
<td>{{ $flag->punch?->punch_time?->format('d M H:i') ?? $flag->attendance_raw_punch_id }}</td>
<td>{{ Str::limit($flag->reason ?? '—', 40) }}</td>
<td>{{ $flag->statusLabel() }}</td>
<td class="text-right">
@if($canManage && $flag->status === 'open')
<form method="POST" action="{{ route('admin.hrm.rmg.proxy-punch.review', $flag) }}" class="inline space-x-1">@csrf
<button type="submit" name="status" value="reviewed" class="erp-btn-secondary !py-1 !px-2 text-[10px]">Review</button>
<button type="submit" name="status" value="dismissed" class="erp-btn-secondary !py-1 !px-2 text-[10px]">Dismiss</button>
</form>
@endIf
</td></tr>
@empty<tr><td colspan="5" class="text-center py-8 text-gray-400">No proxy punch flags yet.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $flags->links() }}</div></div>
@endsection
