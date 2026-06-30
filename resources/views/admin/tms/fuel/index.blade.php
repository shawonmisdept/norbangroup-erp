@extends('layouts.admin')
@section('title', 'Fuel Logs')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Fuel Logs',
    'subtitle' => 'Fuel entries per trip',
    'actions' => auth()->user()->canManageTmsSubmodule('fuel')
        ? '<a href="' . route('admin.tms.fuel.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Entry</a>'
        : '',
])

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Vehicle</th>
                <th>Type</th>
                <th>Qty</th>
                <th>Amount</th>
                <th>Paid By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fuelLogs as $log)
                <tr>
                    <td class="text-xs tabular-nums">{{ $log->created_at?->format('d M Y') }}</td>
                    <td class="text-xs">{{ $log->vehicle?->displayLabel() }}</td>
                    <td class="capitalize text-xs">{{ $log->fuel_type }}</td>
                    <td class="tabular-nums text-xs">{{ $log->quantity }} {{ $log->unit }}</td>
                    <td class="tabular-nums">৳{{ number_format($log->amount, 2) }}</td>
                    <td class="text-xs capitalize">{{ str_replace('_', ' ', $log->paid_by) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-8 text-gray-400">No fuel entries.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($fuelLogs->hasPages())
        <div class="px-4 py-3 border-t">{{ $fuelLogs->links() }}</div>
    @endif
</div>
@endsection
