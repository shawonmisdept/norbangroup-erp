@props(['vehicle', 'canManage' => false])

<div class="erp-panel p-5 space-y-3">
    <h3 class="text-sm font-semibold text-gray-800">Quick Actions</h3>
    <div class="flex flex-col gap-2">
        @if($canManage)
            <a href="{{ route('admin.tms.vehicles.edit', $vehicle) }}" class="erp-btn-sm-primary w-full text-center">Edit Vehicle</a>
        @endif
        <a href="{{ route('admin.tms.vehicles.papers') }}" class="erp-btn-sm-secondary w-full text-center">Papers Status</a>
        @if(auth()->user()->canViewTmsSubmodule('maintenance'))
            <a href="{{ route('admin.tms.maintenance.register', $vehicle) }}" class="erp-btn-sm-secondary w-full text-center">Maintenance Register</a>
        @endif
        @if(auth()->user()->canViewTmsSubmodule('fuel') && auth()->user()->canManageTmsSubmodule('fuel'))
            <a href="{{ route('admin.tms.fuel.create', ['vehicle_id' => $vehicle->id]) }}" class="erp-btn-sm-secondary w-full text-center">Add Fuel Entry</a>
        @elseif(auth()->user()->canViewTmsSubmodule('fuel'))
            <a href="{{ route('admin.tms.fuel.index', ['vehicle_id' => $vehicle->id]) }}" class="erp-btn-sm-secondary w-full text-center">Fuel Logs</a>
        @endif
        @if(auth()->user()->canViewTmsSubmodule('trips'))
            <a href="{{ route('admin.tms.odometer.index', ['vehicle_id' => $vehicle->id]) }}" class="erp-btn-sm-secondary w-full text-center">Daily KM</a>
        @endif
        <a href="{{ route('admin.tms.vehicles.index') }}" class="erp-btn-sm-secondary w-full text-center">All Vehicles</a>
    </div>
</div>
