<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
<div><label class="erp-label">Status</label>
<select name="status" class="erp-input"><option value="">All</option>@foreach($statuses as $k => $l)<option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
<div><label class="erp-label">Pickup Date</label><input type="date" name="pickup_date" class="erp-input" value="{{ $filters['pickup_date'] ?? '' }}"></div>
<div><label class="erp-label">Destination</label><input type="text" name="destination" class="erp-input" value="{{ $filters['destination'] ?? '' }}" placeholder="Search…"></div>
@if($factories !== [])
<div><label class="erp-label">Unit</label>
<select name="factory_id" class="erp-input"><option value="">All</option>@foreach($factories as $id => $name)<option value="{{ $id }}" @selected(($filters['factory_id'] ?? '') == $id)>{{ $name }}</option>@endforeach</select></div>
@endif
<div><button type="submit" class="erp-btn-primary w-full">Filter</button></div>
</form>
