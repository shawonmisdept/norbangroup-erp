@extends('layouts.employee')
@section('title', 'New Transport Request')
@section('content')
<div class="space-y-4">
<div class="flex items-center justify-between gap-2">
<h1 class="text-lg font-bold">New Request</h1>
<a href="{{ route('employee.transport.index') }}" class="emp-btn-secondary">← Back</a>
</div>
<form method="POST" action="{{ route('employee.transport.requests.store') }}" class="space-y-4">
@csrf
<div><label class="emp-label">Pickup Location</label><input type="text" name="pickup_location" class="emp-input" value="{{ old('pickup_location') }}" required></div>
<div><label class="emp-label">Destination (dropdown)</label>
<select name="destination_id" class="emp-input"><option value="">Custom below…</option>@foreach($destinations as $d)<option value="{{ $d->id }}" @selected(old('destination_id') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
<div><label class="emp-label">Custom Destination</label><input type="text" name="destination_custom" class="emp-input" value="{{ old('destination_custom') }}"></div>
<div><label class="emp-label">Pickup Date & Time</label><input type="datetime-local" name="pickup_at" class="emp-input" value="{{ old('pickup_at') }}" required></div>
<div><label class="emp-label">Purpose</label><textarea name="purpose" class="emp-input" rows="3" required>{{ old('purpose') }}</textarea></div>
<div><label class="emp-label">Passengers</label><input type="number" name="passenger_count" class="emp-input" value="{{ old('passenger_count', 1) }}" min="1" required></div>
<button type="submit" class="emp-btn w-full">Submit Request</button>
</form>
</div>
@endsection
