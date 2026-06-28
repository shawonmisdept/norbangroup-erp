@extends('layouts.admin')

@section('title', 'Score Templates')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.performance.hub') }}" class="hover:text-brand">Performance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Templates</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Score Templates',
    'subtitle' => 'Hybrid auto + manual criteria with weighted scoring',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.performance.templates.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">New Template</a>' : ''),
])

@include('admin.hrm.performance.partials.unit-scope-notice')

@if(count($factories) > 1)
<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="w-44">
                <label class="erp-form-label">Factory / Unit</label>
                <select name="factory_id" class="erp-input !text-xs">
                    <option value="">All units</option>
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" {{ (string) ($filters['factory_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary !py-2 !px-4 text-xs">Filter</button>
        </form>
    </div>
</div>
@endif

<div class="erp-panel">
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Factory</th>
                    <th>Criteria</th>
                    <th>Default</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $row)
                    <tr>
                        <td class="font-medium">{{ $row->name }}</td>
                        <td>{{ $row->factory?->name ?? 'All units' }}</td>
                        <td>{{ $row->criteria_count }}</td>
                        <td>{{ $row->is_default ? 'Yes' : '—' }}</td>
                        <td><span class="erp-badge {{ $row->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $row->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-right">
                            @include('partials.erp.table-actions', [
                                'viewUrl' => route('admin.hrm.performance.templates.show', $row),
                                'editUrl' => $canManage ? route('admin.hrm.performance.templates.edit', $row) : null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-400 py-8">No templates. A default template is created on first cycle open.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($templates->hasPages())
        <div class="erp-panel-body border-t border-gray-100">{{ $templates->links() }}</div>
    @endif
</div>
@endsection
