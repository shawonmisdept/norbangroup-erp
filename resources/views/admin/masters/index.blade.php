@php
    $routePrefix = $routePrefix ?? 'admin.masters';
    $hubLabel = $hubLabel ?? 'Master Data';
    $masterNamespace = $masterNamespace ?? 'masters';
    $columnLabels = [
        'code' => 'Code', 'name' => 'Name', 'native_name' => 'Native Name', 'address' => 'Address', 'phone' => 'Phone',
        'company' => 'Company', 'email' => 'Email', 'country' => 'Country',
        'year' => 'Year', 'start_date' => 'Start', 'end_date' => 'End',
        'hex_code' => 'Hex', 'sort_order' => 'Order', 'unit' => 'Unit', 'value' => 'GSM',
        'description' => 'Description', 'calendar_type' => 'Type',
        'branch' => 'Branch', 'account_name' => 'Account', 'account_number' => 'Account No.',
        'routing_number' => 'Routing', 'swift_code' => 'SWIFT',
        'image' => 'Image', 'is_active' => 'Status',
        'factory_id' => 'Factory', 'department_id' => 'Department',
        'building_id' => 'Building', 'floor_id' => 'Floor', 'floor_number' => 'Floor No.',
        'buyer_id' => 'Buyer', 'material_type_id' => 'Material Type', 'supplier_type_id' => 'Supplier Type',
        'date' => 'Date', 'start_time' => 'Start', 'end_time' => 'End', 'is_night' => 'Night',
        'is_paid' => 'Paid', 'is_optional' => 'Optional', 'max_days_per_year' => 'Max Days',
        'ip_address' => 'IP', 'location' => 'Location', 'device_serial' => 'Serial',
    ];
@endphp

@extends('layouts.admin')

@section('title', $config['label_plural'] . ' — ' . config('app.name'))

@section('breadcrumbs')
    <a href="{{ route("{$routePrefix}.hub") }}" class="hover:text-brand">{{ $hubLabel }}</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $config['label_plural'] }}</span>
@endsection

@section('admin-content')

@include('partials.erp.page-header', [
    'title' => $config['label_plural'],
    'subtitle' => 'Manage ' . strtolower($config['label']) . ' reference records',
    'actions' => auth()->user()->canManageMasterModule($masterNamespace, $module)
        ? '<a href="' . route("{$routePrefix}.create", $module) . '" class="erp-btn-primary">+ New ' . $config['label'] . '</a>'
        : null,
])

<div class="erp-panel mb-4">
    <form method="GET" class="erp-panel-body flex flex-wrap gap-2 items-center">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by name or code…"
               class="erp-input flex-1 min-w-48 !text-xs">
        <button type="submit" class="erp-btn-primary">Search</button>
        @if(request('search'))
            <a href="{{ route("{$routePrefix}.index", $module) }}" class="text-xs text-gray-400 hover:text-gray-600 px-2">Clear</a>
        @endif
    </form>
</div>

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">{{ $config['label'] }} Records</h2>
        <span class="text-[11px] text-gray-400">{{ $records->total() }} record(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    @foreach($config['columns'] as $column)
                        <th>{{ $columnLabels[$column] ?? ucfirst(str_replace('_', ' ', $column)) }}</th>
                    @endforeach
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        @foreach($config['columns'] as $column)
                            <td>@include('admin.masters.partials.column', compact('record', 'column', 'module', 'relationColumnsConfig'))</td>
                        @endforeach
                        <td class="text-right">
                            <div class="erp-table-actions">
                                <a href="{{ route("{$routePrefix}.show", [$module, $record]) }}" class="erp-btn-sm-secondary">View</a>
                                @if(auth()->user()->canManageMasterModule($masterNamespace, $module))
                                    <a href="{{ route("{$routePrefix}.edit", [$module, $record]) }}" class="erp-btn-sm-primary">Edit</a>
                                    <form method="POST" action="{{ route("{$routePrefix}.destroy", [$module, $record]) }}" class="inline" data-confirm="Delete this {{ strtolower($config['label']) }}?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="erp-btn-danger !py-1 !px-2">Del</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($config['columns']) + 1 }}" class="text-center py-10 text-gray-400">
                            No {{ strtolower($config['label_plural']) }} found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
        <div class="px-4 py-3 border-t border-erp-border bg-gray-50/50">{{ $records->links() }}</div>
    @endif
</div>
@endsection
