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
