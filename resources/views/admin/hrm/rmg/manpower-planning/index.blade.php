@extends('layouts.admin')
@section('title', 'Manpower Planning')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Manpower Planning',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.rmg.manpower-planning.create') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">New Plan</a>' : '')
        . '<a href="' . route('admin.hrm.rmg.hub') . '" class="erp-btn-secondary ml-2">← Hub</a>',
])
@if($summary !== [])
<div class="erp-panel mb-3"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Line</th><th>Required</th><th>Present</th><th>Variance</th></tr></thead>
<tbody>@foreach($summary as $row)
<tr><td>{{ $row['line_name'] }}</td><td>{{ $row['required_count'] }}</td><td>{{ $row['present_count'] }}</td>
<td class="{{ $row['variance'] < 0 ? 'text-red-600 font-semibold' : '' }}">{{ $row['variance'] }}</td></tr>
@endforeach</tbody></table></div></div>
@endIf
<div class="erp-panel"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Line</th><th>Plan Date</th><th>Required</th><th>Notes</th></tr></thead>
<tbody>@forelse($plans as $plan)
<tr><td>{{ $plan->line?->name }}</td><td>{{ $plan->plan_date?->format('d M Y') }}</td><td>{{ $plan->required_count }}</td><td>{{ $plan->notes ?? '—' }}</td></tr>
@empty<tr><td colspan="4" class="text-center py-8 text-gray-400">No manpower plans yet.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $plans->links() }}</div></div>
@endsection
