@extends('layouts.admin')
@section('title', 'Destinations')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Destinations',
    'subtitle' => 'Predefined transport destinations',
    'actions' => auth()->user()->canManageTmsSubmodule('destinations')
        ? '<a href="' . route('admin.tms.destinations.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Destination</a>'
        : '',
])

<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-4 gap-3 items-end">
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
    <div class="flex gap-2">
        <button type="submit" class="erp-btn-primary">Apply</button>
        <a href="{{ route('admin.tms.destinations.index') }}" class="erp-btn-secondary">Reset</a>
    </div>
</form>

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Unit</th>
                <th>Name</th>
                <th>Address</th>
                <th>Active</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($destinations as $d)
                <tr>
                    <td class="text-xs">{{ $d->factory?->name }}</td>
                    <td>{{ $d->name }}</td>
                    <td class="text-xs">{{ $d->address ?? '—' }}</td>
                    <td>
                        <span class="erp-badge {{ $d->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ $d->is_active ? 'Yes' : 'No' }}
                        </span>
                    </td>
                    <td class="text-right">
                        @if(auth()->user()->canManageTmsSubmodule('destinations'))
                            @include('admin.tms.partials.row-actions', [
                                'editUrl' => route('admin.tms.destinations.edit', $d),
                                'destroyUrl' => route('admin.tms.destinations.destroy', $d),
                            ])
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-8 text-gray-400">No destinations yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($destinations->hasPages())
        <div class="px-4 py-3 border-t">{{ $destinations->links() }}</div>
    @endif
</div>
@endsection
