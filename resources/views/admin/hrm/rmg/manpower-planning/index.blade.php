@extends('layouts.admin')
@section('title', 'Manpower Planning')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Manpower Planning',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.rmg.manpower-planning.create') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">New Plan</a>' : '')
        . '<a href="' . route('admin.hrm.rmg.hub') . '" class="erp-btn-secondary ml-2">← Hub</a>',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="erp-filter-bar">
            <div class="erp-filter-field">
                <label class="erp-form-label">Factory</label>
                <select name="factory_id" class="erp-input !text-xs" onchange="this.form.submit()">
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" {{ (string)($filters['factory_id'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="erp-filter-field">
                <label class="erp-form-label">Plan Date</label>
                <input type="date" name="plan_date" value="{{ $filters['plan_date'] ?? '' }}" class="erp-input !text-xs" onchange="this.form.submit()">
            </div>
        </form>
    </div>
</div>

@if($summary !== [])
<div class="erp-panel mb-3"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Line</th><th>Required</th><th>Present</th><th>Variance</th></tr></thead>
<tbody>@foreach($summary as $row)
<tr><td>{{ $row['line_name'] }}</td><td>{{ $row['required_count'] }}</td><td>{{ $row['present_count'] }}</td>
<td class="{{ $row['variance'] < 0 ? 'text-red-600 font-semibold' : '' }}">{{ $row['variance'] }}</td></tr>
@endforeach</tbody></table></div></div>
@endIf
<div class="erp-panel"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Line</th><th>Plan Date</th><th>Required</th><th>Notes</th><th></th></tr></thead>
<tbody>@forelse($plans as $plan)
<tr>
<td>{{ $plan->line?->name }}</td>
<td>{{ $plan->plan_date?->format('d M Y') }}</td>
<td>{{ $plan->required_count }}</td>
<td>{{ $plan->notes ?? '—' }}</td>
<td class="text-right">
@if($canManage)
    @include('partials.erp.table-actions', [
        'editUrl' => route('admin.hrm.rmg.manpower-planning.edit', $plan),
        'destroyUrl' => route('admin.hrm.rmg.manpower-planning.destroy', $plan),
        'destroyConfirm' => 'Delete this manpower plan?',
    ])
@endif
</td>
</tr>
@empty<tr><td colspan="5" class="text-center py-8 text-gray-400">No manpower plans yet.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $plans->links() }}</div></div>
@endsection
