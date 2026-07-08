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
<td>@if($flag->punch?->punch_time)@portalDateTimeShort($flag->punch->punch_time)@else{{ $flag->attendance_raw_punch_id }}@endif</td>
<td>{{ Str::limit($flag->reason ?? '—', 40) }}</td>
<td>{{ $flag->statusLabel() }}</td>
<td class="text-right">
@if($canManage && $flag->status === 'open')
    <div class="erp-table-actions justify-end">
        <form method="POST" action="{{ route('admin.hrm.rmg.proxy-punch.review', $flag) }}" class="inline"
              data-confirm="Mark this proxy punch flag as reviewed?"
              data-confirm-variant="primary"
              data-confirm-ok="Yes, mark reviewed">@csrf
            <input type="hidden" name="status" value="reviewed">
            <button type="submit" class="erp-btn-secondary !py-1 !px-2 text-[10px]">Review</button>
        </form>
        <form method="POST" action="{{ route('admin.hrm.rmg.proxy-punch.review', $flag) }}" class="inline"
              data-confirm="Dismiss this proxy punch flag?"
              data-confirm-variant="warning"
              data-confirm-ok="Yes, dismiss">@csrf
            <input type="hidden" name="status" value="dismissed">
            <button type="submit" class="erp-btn-secondary !py-1 !px-2 text-[10px] !text-red-600">Dismiss</button>
        </form>
    </div>
@endIf
</td></tr>
@empty<tr><td colspan="5" class="text-center py-8 text-gray-400">No proxy punch flags yet.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $flags->links() }}</div></div>
@endsection
