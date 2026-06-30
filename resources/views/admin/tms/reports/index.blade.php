@extends('layouts.admin')
@section('title', 'TMS Reports')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'TMS Reports', 'subtitle' => 'Filter and view all logs on screen'])

@include('admin.tms.reports.partials.tabs')
@include('admin.tms.reports.partials.filters')

@if($tab === 'fleet_cost' && $summary)
    @include('admin.tms.reports.partials.fleet-cost-summary')
@else
    <div class="erp-panel overflow-hidden">
        <table class="erp-table">
            @if($tab === 'requests')
                @include('admin.tms.reports.partials.requests-table')
            @elseif($tab === 'trips')
                @include('admin.tms.reports.partials.trips-table')
            @elseif($tab === 'fuel')
                @include('admin.tms.reports.partials.fuel-table')
            @elseif($tab === 'odometer')
                @include('admin.tms.reports.partials.odometer-table')
            @elseif($tab === 'maintenance')
                @include('admin.tms.reports.partials.maintenance-table')
            @elseif($tab === 'rental_charges')
                @include('admin.tms.reports.partials.rental-charges-table')
            @else
                @include('admin.tms.reports.partials.driver-pay-table')
            @endif
        </table>

        @if($rows && $rows->hasPages())
            <div class="px-4 py-3 border-t">{{ $rows->links() }}</div>
        @endif
    </div>
@endif
@endsection
