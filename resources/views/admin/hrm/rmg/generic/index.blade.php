@extends('layouts.admin')
@section('title', $config['label'])
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $config['label'],
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.rmg.' . $submodule . '.create') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">New Entry</a>' : '')
        . '<a href="' . route('admin.hrm.rmg.hub') . '" class="erp-btn-secondary ml-2">← Hub</a>',
])
<div class="erp-panel"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr>@foreach($columns as $col)<th>{{ $col }}</th>@endforeach<th></th></tr></thead>
<tbody>@forelse($records as $record)
<tr>
@switch($submodule)
@case('osd-movement')
<td>{{ $record->employee?->name }}</td><td>{{ $record->typeLabel() }}</td><td>{{ $record->start_date?->format('d M') }} – {{ $record->end_date?->format('d M Y') }}</td><td>{{ $record->statusLabel() }}</td>
@break
@case('canteen')
<td>{{ $record->employee?->name }}</td><td>{{ $record->period_year }}-{{ str_pad($record->period_month, 2, '0', STR_PAD_LEFT) }}</td><td>{{ $record->meal_count }}</td><td>৳{{ number_format($record->amount, 2) }}</td>
@break
@case('medical')
<td>{{ $record->employee?->name }}</td><td>{{ $record->visit_date?->format('d M Y') }}</td><td>{{ $record->complaint ?? '—' }}</td><td>{{ $record->referred ? 'Yes' : 'No' }}</td>
@break
@case('training')
<td>{{ $record->employee?->name }}</td><td>{{ $record->typeLabel() }}</td><td>{{ $record->title }}</td><td>{{ $record->training_date?->format('d M Y') }}</td>
@break
@case('sub-contract')
<td>{{ $record->agency_name }}</td><td>{{ $record->name }}</td><td>{{ $record->line?->name ?? '—' }}</td><td>{{ $record->statusLabel() }}</td>
@break
@case('buyer-holiday')
<td>{{ $record->buyer?->name }}</td><td>{{ $record->name }}</td><td>{{ $record->date?->format('d M Y') }}</td><td>{{ $record->is_active ? 'Yes' : 'No' }}</td>
@break
@case('salary-hold')
<td>{{ $record->employee?->name }}</td><td>{{ $record->hold_from?->format('d M Y') }}</td><td>{{ $record->hold_until?->format('d M Y') ?? '—' }}</td><td>{{ $record->statusLabel() }}</td>
@break
@case('production-incentive')
<td>{{ $record->line?->name }}</td><td>{{ $record->period_year }}-{{ str_pad($record->period_month, 2, '0', STR_PAD_LEFT) }}</td><td>{{ $record->output_qty }}</td><td>৳{{ number_format($record->total_amount, 2) }}</td><td>{{ $record->statusLabel() }}</td>
@break
@endswitch
<td class="text-right">
@if($canManage && $submodule === 'salary-hold' && $record->status === 'active')
<form method="POST" action="{{ route('admin.hrm.rmg.salary-hold.release', $record) }}" class="inline">@csrf<button type="submit" class="erp-btn-primary !py-1 !px-2 text-[10px]">Release</button></form>
@elseif($canManage && $submodule === 'production-incentive' && $record->status === 'draft')
<form method="POST" action="{{ route('admin.hrm.rmg.production-incentive.approve', $record) }}" class="inline">@csrf<button type="submit" class="erp-btn-primary !py-1 !px-2 text-[10px]">Approve</button></form>
@elseif($canManage && $submodule === 'osd-movement' && $record->status === 'pending')
<form method="POST" action="{{ route('admin.hrm.rmg.osd-movement.approve', $record) }}" class="inline">@csrf<button type="submit" class="erp-btn-primary !py-1 !px-2 text-[10px]">Approve</button></form>
<form method="POST" action="{{ route('admin.hrm.rmg.osd-movement.reject', $record) }}" class="inline ml-1" data-confirm="Reject this OSD movement?">@csrf<button type="submit" class="erp-btn-secondary !py-1 !px-2 text-[10px] !text-red-600">Reject</button></form>
@endif
@php
    $canEdit = $canManage && (
        in_array($submodule, ['canteen', 'medical', 'training', 'buyer-holiday', 'sub-contract'], true)
        || ($submodule === 'osd-movement' && $record->status === 'pending')
        || ($submodule === 'production-incentive' && $record->status === 'draft')
    );
    $canDelete = $canManage && (
        in_array($submodule, ['canteen', 'medical', 'training', 'buyer-holiday', 'sub-contract'], true)
        || ($submodule === 'osd-movement' && in_array($record->status, ['pending', 'rejected'], true))
        || ($submodule === 'salary-hold' && $record->status === 'released')
        || ($submodule === 'production-incentive' && $record->status === 'draft')
    );
@endphp
@if($canEdit || $canDelete)
    @include('partials.erp.table-actions', [
        'editUrl' => $canEdit ? route('admin.hrm.rmg.' . $submodule . '.edit', $record) : null,
        'destroyUrl' => $canDelete ? route('admin.hrm.rmg.' . $submodule . '.destroy', $record) : null,
    ])
@endif
</td></tr>
@empty<tr><td colspan="{{ count($columns) + 1 }}" class="text-center py-8 text-gray-400">No records yet.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $records->links() }}</div></div>
@endsection
