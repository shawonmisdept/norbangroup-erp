@extends('layouts.admin')

@section('title', 'Increment Bands')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.performance.hub') }}" class="hover:text-brand">Performance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Increment Bands</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Annual Increment Bands',
    'subtitle' => 'Score % → salary increment % from approved annual reviews',
])

<div class="flex flex-wrap items-center justify-end gap-2 mb-4">
    @if($canManage && $factoryId)
        <a href="{{ route('admin.hrm.performance.increment-bands.edit', ['factory_id' => $factoryId]) }}" class="erp-btn-primary !py-2 !px-4 text-xs">Edit Bands</a>
    @endif
</div>

@include('admin.hrm.performance.partials.unit-scope-notice')

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="w-56">
                <label class="erp-form-label">Factory</label>
                <select name="factory_id" class="erp-input !text-xs" required onchange="this.form.submit()">
                    <option value="">Select factory</option>
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" {{ (string) $factoryId === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

@if($factoryId)
    <div class="erp-panel">
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Band</th>
                        <th>Score Range</th>
                        <th>Increment %</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bands as $band)
                        <tr>
                            <td class="font-medium">{{ $band->name }}</td>
                            <td>{{ number_format($band->min_score, 0) }}% – {{ number_format($band->max_score, 2) }}%</td>
                            <td>{{ number_format($band->increment_percent, 1) }}%</td>
                            <td><span class="erp-badge {{ $band->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }} text-[10px]">{{ $band->is_active ? 'Active' : 'Inactive' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-gray-400 py-8">No bands configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="erp-panel"><div class="erp-panel-body text-sm text-gray-500">Select a factory to view increment bands.</div></div>
@endif
@endsection
