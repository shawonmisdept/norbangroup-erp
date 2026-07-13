<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
    <input type="hidden" name="tab" value="{{ $tab }}">

    <div>
        <label class="erp-label">From</label>
        <input type="date" name="from" class="erp-input" value="{{ $filters['from'] ?? '' }}">
    </div>

    <div>
        <label class="erp-label">To</label>
        <input type="date" name="to" class="erp-input" value="{{ $filters['to'] ?? '' }}">
    </div>

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

    @if(in_array($tab, ['fuel', 'trips', 'maintenance']))
        <div>
            <label class="erp-label">Vehicle</label>
            <select name="vehicle_id" class="erp-input">
                <option value="">All</option>
                @foreach(($vehicles ?? collect()) as $vehicle)
                    <option value="{{ $vehicle->id }}" @selected(($filters['vehicle_id'] ?? '') == $vehicle->id)>{{ $vehicle->displayLabel() }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if($tab === 'fuel')
        <div>
            <label class="erp-label">View</label>
            <select name="fuel_view" class="erp-input">
                <option value="detail" @selected(($filters['fuel_view'] ?? 'detail') === 'detail')>Detail</option>
                <option value="by_vehicle" @selected(($filters['fuel_view'] ?? '') === 'by_vehicle')>By Vehicle</option>
            </select>
        </div>
    @endif

    @if(in_array($tab, ['requests', 'requests_by_department', 'department_chargeback']))
        <div>
            <label class="erp-label">Department</label>
            <select name="department_id" class="erp-input">
                <option value="">All</option>
                @foreach($departments as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['department_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if($tab === 'requests')
        <div>
            <label class="erp-label">Status</label>
            <select name="status" class="erp-input">
                <option value="">All</option>
                @foreach($statuses as $k => $l)
                    <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if(in_array($tab, ['ot', 'rental_charges', 'payroll_ot']))
        <div>
            <label class="erp-label">Payment</label>
            <select name="payment_status" class="erp-input">
                <option value="">All</option>
                <option value="pending" @selected(($filters['payment_status'] ?? '') === 'pending')>Pending</option>
                <option value="paid" @selected(($filters['payment_status'] ?? '') === 'paid')>Paid</option>
            </select>
        </div>
    @endif

    <div class="flex gap-2">
        <button type="submit" class="erp-btn-primary">Apply</button>
        @php
            $exportReport = ($tab === 'fuel' && ($filters['fuel_view'] ?? 'detail') === 'by_vehicle')
                ? 'fuel_by_vehicle'
                : $tab;
        @endphp
        <a href="{{ route('admin.tms.reports.export', array_merge($filters, ['report' => $exportReport])) }}" class="erp-btn-secondary">Export CSV</a>
    </div>
</form>
