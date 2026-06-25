@extends('layouts.admin')
@section('title', ($policy->exists ? 'Edit' : 'Add') . ' Leave Policy')
@section('admin-content')
@include('partials.erp.page-header', ['title' => ($policy->exists ? 'Edit' : 'Add') . ' Leave Policy', 'actions' => '<a href="' . route('admin.hrm.leave.policies.index') . '" class="erp-btn-secondary">← Back</a>'])
@include('admin.hrm.partials.submodule-nav', ['section' => 'leave', 'current' => 'policies'])
<div class="erp-panel max-w-xl"><div class="erp-panel-body">
<form method="POST" action="{{ $policy->exists ? route('admin.hrm.leave.policies.update', $policy) : route('admin.hrm.leave.policies.store') }}" class="space-y-3">@csrf @if($policy->exists)@method('PUT')@endif
<div><label class="erp-form-label">Factory</label><select name="factory_id" required class="erp-input !text-xs">@foreach($factories as $id=>$n)<option value="{{ $id }}" {{ (string)old('factory_id',$policy->factory_id)===(string)$id?'selected':'' }}>{{ $n }}</option>@endforeach</select></div>
<div><label class="erp-form-label">Leave Type</label><select name="leave_type_id" required class="erp-input !text-xs">@foreach($leaveTypes as $t)<option value="{{ $t->id }}" {{ (string)old('leave_type_id',$policy->leave_type_id)===(string)$t->id?'selected':'' }}>{{ $t->name }}</option>@endforeach</select></div>
<div><label class="erp-form-label">Days per Year</label><input type="number" step="0.5" name="days_per_year" value="{{ old('days_per_year', $policy->days_per_year) }}" required class="erp-input !text-xs"></div>
<div><label class="erp-form-label">Min Notice (days)</label><input type="number" name="min_days_notice" value="{{ old('min_days_notice', $policy->min_days_notice) }}" class="erp-input !text-xs"></div>
<div><label class="erp-form-label">Medical cert after (days)</label><input type="number" name="requires_medical_after_days" value="{{ old('requires_medical_after_days', $policy->requires_medical_after_days) }}" class="erp-input !text-xs"></div>
<label class="flex items-center gap-2 text-xs"><input type="hidden" name="requires_attachment" value="0"><input type="checkbox" name="requires_attachment" value="1" {{ old('requires_attachment',$policy->requires_attachment)?'checked':'' }}> Requires attachment</label>
<label class="flex items-center gap-2 text-xs"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" {{ old('is_active',$policy->is_active??true)?'checked':'' }}> Active</label>
<button type="submit" class="erp-btn-primary">Save</button>
</form></div></div>
@endsection
