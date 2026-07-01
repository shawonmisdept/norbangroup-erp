@php
    $tabs = [
        'requests'       => 'Requests',
        'trips'          => 'Trips',
        'fuel'           => 'Fuel',
        'odometer'       => 'Daily KM',
        'ot'             => 'Driver Pay',
        'maintenance'    => 'Maintenance',
        'rental_charges'       => 'Rental Charges',
        'fleet_cost'           => 'Vehicle Management Cost',
        'requests_by_department' => 'Requests by Dept',
        'department_chargeback'  => 'Dept Charge-back',
        'payroll_ot'             => 'Payroll OT Export',
    ];
@endphp

<div class="flex flex-wrap gap-2 mb-4">
    @foreach($tabs as $key => $label)
        <a href="{{ route('admin.tms.reports.index', array_merge($filters, ['tab' => $key])) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $tab === $key ? 'bg-brand text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
