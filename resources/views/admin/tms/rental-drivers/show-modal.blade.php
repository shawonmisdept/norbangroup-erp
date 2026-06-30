<input type="hidden" class="tms-modal-title" value="Rental Driver — {{ $driver->name }}">

<div class="space-y-5">
    <div class="flex flex-col sm:flex-row gap-4 pb-4 border-b border-erp-border">
        @include('partials.rental-driver-avatar', ['driver' => $driver, 'size' => '180'])
        <div class="min-w-0 flex-1">
            <h4 class="text-lg font-semibold text-gray-900">{{ $driver->name }}</h4>
            <p class="text-sm text-gray-500 mt-0.5">{{ $driver->factory?->name ?? '—' }}</p>
            <span class="inline-flex mt-2 erp-badge {{ $driver->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                {{ ucfirst($driver->status) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Mobile</span>
            <span class="font-medium tabular-nums">{{ $driver->mobile ?? '—' }}</span>
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">NID Number</span>
            <span class="font-medium tabular-nums">{{ $driver->nid_number ?? '—' }}</span>
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">License Number</span>
            <span class="font-medium tabular-nums">{{ $driver->license_number ?? '—' }}</span>
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Vendor / Company</span>
            <span class="font-medium">{{ $driver->vendorLabel() }}</span>
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Default Vehicle</span>
            <span class="font-medium">{{ $driver->defaultVehicle?->displayLabel() ?? '—' }}</span>
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Portal Access</span>
            @if($driver->portalUser)
                <span class="text-green-700 font-medium">Active</span>
                @if($driver->portalUser->last_login_at)
                    <span class="block text-xs text-gray-500 mt-0.5">Last login {{ $driver->portalUser->last_login_at->format('d M Y, H:i') }}</span>
                @endif
            @else
                <span class="text-gray-400">Not enabled</span>
            @endif
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Created</span>
            {{ $driver->created_at?->format('d M Y, H:i') ?? '—' }}
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Last Updated</span>
            {{ $driver->updated_at?->format('d M Y, H:i') ?? '—' }}
        </div>
    </div>

    @if($driver->notes)
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Notes</span>
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $driver->notes }}</p>
        </div>
    @endif

    @if($canManage ?? false)
        <div class="flex flex-wrap gap-2 pt-2 border-t border-erp-border">
            <a href="{{ route('admin.tms.rental-drivers.edit', $driver) }}" class="erp-btn-sm-primary">Edit Driver</a>
        </div>
    @endif
</div>
