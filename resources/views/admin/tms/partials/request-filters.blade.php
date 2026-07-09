<form method="GET" class="erp-panel mb-4">
    <div class="erp-panel-body">
        <div class="erp-filter-bar">
            <div class="erp-filter-field">
                <label class="erp-label">Status</label>
                <select name="status" class="erp-input">
                    <option value="">All</option>
                    @foreach($statuses as $k => $l)
                        <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            <div class="erp-filter-field">
                <label class="erp-label">Pickup Date</label>
                <input type="date" name="pickup_date" class="erp-input" value="{{ $filters['pickup_date'] ?? '' }}">
            </div>

            <div class="erp-filter-field-grow">
                <label class="erp-label">Destination</label>
                <input type="text" name="destination" class="erp-input" value="{{ $filters['destination'] ?? '' }}" placeholder="Search…">
            </div>

            @if($factories !== [])
                <div class="erp-filter-field">
                    <label class="erp-label">Unit</label>
                    <select name="factory_id" class="erp-input">
                        <option value="">All</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" @selected(($filters['factory_id'] ?? '') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="erp-filter-actions">
                <button type="submit" class="erp-btn-primary">Filter</button>
                @if(array_filter($filters ?? []))
                    <a href="{{ route('admin.tms.requests.index') }}" class="erp-btn-secondary">Clear</a>
                @endif
            </div>
        </div>
    </div>
</form>
