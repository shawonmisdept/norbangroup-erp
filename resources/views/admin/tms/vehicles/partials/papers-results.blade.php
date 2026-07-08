<div class="vehicle-papers-results">
    <p class="vehicle-papers-scroll-hint" aria-hidden="true">Swipe table for more columns →</p>
    <div class="vehicle-papers-legend">
    <span><i class="bg-amber-200"></i> Warning (≤60 days)</span>
    <span><i class="bg-orange-300"></i> Urgent (≤30 days)</span>
    <span><i class="bg-red-300"></i> Expired</span>
    <span><i class="bg-gray-100"></i> Missing / N/A</span>
    @if($paginator->total() > 0)
        <span class="vehicle-papers-count">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</span>
    @endif
</div>
<table class="vehicle-papers-table">
    <colgroup>
        <col class="col-num">
        <col class="col-unit">
        <col class="col-vehicle">
        <col class="col-reg">
        <col class="col-year">
        <col class="col-cc">
        <col class="col-fuel">
        @foreach($paperTypes as $label)
            <col class="col-paper">
        @endforeach
        <col class="col-reg-status">
        <col class="col-assign">
        <col class="col-assign">
        <col class="col-assign">
        <col class="col-actions">
    </colgroup>
    <thead>
        <tr>
            <th>#</th>
            <th class="vehicle-papers-col-mobile-hide">Unit</th>
            <th class="vehicle-papers-sticky-left">Vehicle</th>
            <th>Reg No</th>
            <th class="text-center vehicle-papers-col-mobile-hide">Year</th>
            <th class="text-center vehicle-papers-col-mobile-hide">CC</th>
            <th class="vehicle-papers-col-mobile-hide">Fuel</th>
            @foreach($paperTypes as $label)
                <th class="vehicle-papers-th-paper">{{ $label }}</th>
            @endforeach
            <th class="text-center">Reg. Status</th>
            <th class="vehicle-papers-col-mobile-hide">User</th>
            <th class="vehicle-papers-col-mobile-hide">Driver</th>
            <th class="vehicle-papers-col-mobile-hide">Contact</th>
            <th class="text-right vehicle-papers-sticky-right">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($vehicles as $i => $v)
            @php
                $papers = $paperService->papersForVehicle($v);
                $userLabel = $v->allocatedUserLabel();
                $driverNames = $v->assignedDriverNames();
                $driverContact = $v->primaryDriverContact();
            @endphp
            <tr>
                <td class="vehicle-papers-num">{{ $paginator->firstItem() + $i }}</td>
                <td class="vehicle-papers-unit vehicle-papers-col-mobile-hide" title="{{ $v->factory?->name }}">{{ $v->factory?->name }}</td>
                <td class="vehicle-papers-sticky-left" title="{{ $v->name }}">
                    <a href="{{ route('admin.tms.vehicles.show', $v) }}" class="vehicle-papers-name">{{ $v->name }}</a>
                </td>
                <td class="vehicle-papers-reg" title="{{ $v->reg_number }}">{{ $v->reg_number }}</td>
                <td class="vehicle-papers-year vehicle-papers-col-mobile-hide">{{ $v->model_year ?? '—' }}</td>
                <td class="vehicle-papers-cc vehicle-papers-col-mobile-hide">{{ $v->engine_cc ? number_format($v->engine_cc) : '—' }}</td>
                <td class="text-xs vehicle-papers-col-mobile-hide" title="{{ config('tms.fuel_types.' . $v->fuel_type, $v->fuel_type) ?: '—' }}">{{ config('tms.fuel_types.' . $v->fuel_type, $v->fuel_type) ?: '—' }}</td>
                @foreach($papers as $paper)
                    <td class="vehicle-papers-date {{ $paperService->statusCellClass($paper['status']) }}" title="{{ $paper['expires_at']?->format('d-M-y') ?? 'N/A' }}">
                        {{ $paper['expires_at']?->format('d-M-y') ?? 'N/A' }}
                    </td>
                @endforeach
                <td class="vehicle-papers-reg-status">
                    <span class="erp-badge {{ $v->registration_paper_status === 'ok' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ config('tms.registration_paper_statuses.' . $v->registration_paper_status, $v->registration_paper_status) }}
                    </span>
                </td>
                <td class="vehicle-papers-user vehicle-papers-col-mobile-hide" title="{{ $userLabel }}">{{ $userLabel ?? '—' }}</td>
                <td class="vehicle-papers-driver vehicle-papers-col-mobile-hide" title="{{ $driverNames }}">{{ $driverNames ?: '—' }}</td>
                <td class="vehicle-papers-contact vehicle-papers-col-mobile-hide" title="{{ $driverContact }}">{{ $driverContact ?? '—' }}</td>
                <td class="vehicle-papers-actions vehicle-papers-sticky-right">
                    @include('partials.erp.table-actions', [
                        'viewUrl' => route('admin.tms.vehicles.show', $v),
                        'editUrl' => auth()->user()->canManageTmsSubmodule('vehicles') ? route('admin.tms.vehicles.edit', $v) : null,
                    ])
                </td>
            </tr>
        @empty
            <tr><td colspan="16" class="text-center py-10 text-gray-400">No vehicles found.</td></tr>
        @endforelse
    </tbody>
</table>

@if($paginator->hasPages())
    <div class="vehicle-papers-pagination px-4 py-3 border-t border-gray-200">{{ $paginator->links() }}</div>
@endif
</div>
