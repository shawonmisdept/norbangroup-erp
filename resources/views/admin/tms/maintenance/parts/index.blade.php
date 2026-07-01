@extends('layouts.admin')
@section('title', 'Maintenance Parts')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Maintenance Parts Catalog',
    'subtitle' => 'Reusable parts & services linked to maintenance bills',
    'actions' => ($canManage ?? false)
        ? '<a href="' . route('admin.tms.maintenance.parts.create') . '" class="erp-btn-primary">+ Add Part</a>'
        : '',
])

<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
    @if($factories !== [])
        <div>
            <label class="erp-label">Unit</label>
            <select name="factory_id" class="erp-input">
                <option value="">All</option>
                @foreach($factories as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['factory_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div>
        <label class="erp-label">Search</label>
        <input type="text" name="search" class="erp-input" value="{{ $filters['search'] ?? '' }}" placeholder="Part name">
    </div>
    <label class="flex items-center gap-2 text-sm pb-2">
        <input type="checkbox" name="inactive" value="1" @checked($filters['inactive'] ?? false)>
        Show inactive only
    </label>
    <div class="flex gap-2">
        <button type="submit" class="erp-btn-primary">Apply</button>
        <a href="{{ route('admin.tms.maintenance.parts.index') }}" class="erp-btn-secondary">Reset</a>
    </div>
</form>

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Unit</th>
                <th class="text-right">Default Price</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($parts as $part)
                <tr>
                    <td class="font-medium">{{ $part->name }}</td>
                    <td>{{ $part->unit ?? '—' }}</td>
                    <td class="text-right tabular-nums">{{ $part->default_unit_price !== null ? '৳' . number_format((float) $part->default_unit_price, 2) : '—' }}</td>
                    <td><span class="erp-badge {{ $part->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $part->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-right">
                        @if($canManage ?? false)
                            <a href="{{ route('admin.tms.maintenance.parts.edit', $part) }}" class="erp-btn-sm-secondary">Edit</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-8 text-gray-400">No parts in catalog.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($parts->hasPages())
        <div class="px-4 py-3 border-t">{{ $parts->links() }}</div>
    @endif
</div>
@endsection
