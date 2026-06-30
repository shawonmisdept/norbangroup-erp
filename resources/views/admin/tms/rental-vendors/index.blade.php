@extends('layouts.admin')
@section('title', 'Rental Vendors')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Rental Vendors',
    'subtitle' => 'Vehicle rental companies and KM rates',
    'actions' => auth()->user()->canManageTmsSubmodule('rental_vendors')
        ? '<a href="' . route('admin.tms.rental-vendors.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Vendor</a>'
        : '',
])

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Unit</th>
                <th>Name</th>
                <th>Contact</th>
                <th>KM Rate</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($vendors as $vendor)
                <tr>
                    <td class="text-xs">{{ $vendor->factory?->name }}</td>
                    <td>{{ $vendor->name }}</td>
                    <td class="text-xs">
                        {{ $vendor->contact_person ?? '—' }}
                        @if($vendor->mobile)
                            <br>{{ $vendor->mobile }}
                        @endif
                    </td>
                    <td class="text-xs">
                        {{ $vendor->rental_km_rate !== null ? '৳' . number_format($vendor->rental_km_rate, 2) . '/km' : 'Factory default' }}
                    </td>
                    <td>
                        <span class="erp-badge {{ $vendor->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($vendor->status) }}
                        </span>
                    </td>
                    <td class="text-right">
                        @if(auth()->user()->canManageTmsSubmodule('rental_vendors'))
                            @include('admin.tms.partials.row-actions', [
                                'editUrl' => route('admin.tms.rental-vendors.edit', $vendor),
                                'destroyUrl' => route('admin.tms.rental-vendors.destroy', $vendor),
                            ])
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-8 text-gray-400">No rental vendors yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($vendors->hasPages())
        <div class="px-4 py-3 border-t">{{ $vendors->links() }}</div>
    @endif
</div>
@endsection
