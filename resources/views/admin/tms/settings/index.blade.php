@extends('layouts.admin')
@section('title', 'TMS Settings')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'TMS Settings', 'subtitle' => 'Office time and OT basis per unit'])
<div class="erp-panel p-6 max-w-2xl">
<form method="GET" class="mb-6 flex gap-3 items-end">
<div class="flex-1"><label class="erp-label">Unit</label>
<select name="factory_id" class="erp-input" onchange="this.form.submit()">
<option value="">Select unit…</option>
@foreach($factories as $id => $name)<option value="{{ $id }}" @selected($factoryId == $id)>{{ $name }}</option>@endforeach
</select></div>
</form>
@if($settings)
<form method="POST" action="{{ route('admin.tms.settings.update') }}" class="space-y-4">
@csrf @method('PUT')
<input type="hidden" name="factory_id" value="{{ $settings->factory_id }}">
<div class="grid grid-cols-2 gap-4">
<div><label class="erp-label">Office Start</label><input type="time" name="office_start" class="erp-input" value="{{ substr($settings->office_start, 0, 5) }}" required></div>
<div><label class="erp-label">Office End</label><input type="time" name="office_end" class="erp-input" value="{{ substr($settings->office_end, 0, 5) }}" required></div>
</div>
<div><label class="erp-label">OT Basis</label>
<select name="ot_basis" class="erp-input">@foreach($otBasis as $k => $l)<option value="{{ $k }}" @selected($settings->ot_basis === $k)>{{ $l }}</option>@endforeach</select></div>
@if(auth()->user()->canManageTmsSubmodule('settings'))
<button type="submit" class="erp-btn-primary">Save Settings</button>
@endif
</form>
@else
<p class="text-sm text-gray-500">Select a unit to configure TMS settings.</p>
@endif
</div>
@endsection
