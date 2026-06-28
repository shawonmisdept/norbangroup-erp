@extends('layouts.admin')

@section('title', 'Increment Runs')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.performance.hub') }}" class="hover:text-brand">Performance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Increment Runs</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Annual Increment Runs',
    'subtitle' => 'Calculate & apply salary increments from approved annual reviews',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.performance.increment-runs.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">New Increment Run</a>' : ''),
])

@include('admin.hrm.performance.partials.unit-scope-notice')

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            @if(count($factories) > 1)
                <div class="w-44">
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" class="erp-input !text-xs">
                        <option value="">All</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ (string) ($filters['factory_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="w-36">
                <label class="erp-form-label">Status</label>
                <select name="status" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary !py-2 !px-4 text-xs">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel">
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead><tr><th>Name</th><th>Factory</th><th>Year</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse($runs as $row)
                    <tr>
                        <td class="font-medium">{{ $row->name }}</td>
                        <td>{{ $row->factory?->name }}</td>
                        <td>{{ $row->year }}</td>
                        <td><span class="erp-badge {{ $row->status === 'applied' ? 'bg-green-100 text-green-700' : ($row->status === 'calculated' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600') }} text-[10px]">{{ $row->statusLabel() }}</span></td>
                        <td class="text-right">
                            @include('partials.erp.table-actions', [
                                'viewUrl' => route('admin.hrm.performance.increment-runs.show', $row),
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-gray-400 py-8">No increment runs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($runs->hasPages())
        <div class="erp-panel-body border-t border-gray-100">{{ $runs->links() }}</div>
    @endif
</div>
@endsection
