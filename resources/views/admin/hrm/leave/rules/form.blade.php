@extends('layouts.admin')
@section('title', 'Leave Rule')
@section('admin-content')
@include('partials.erp.page-header', ['title' => ($rule->exists?'Edit':'Add').' Leave Rule', 'actions'=>'<a href="'.route('admin.hrm.leave.rules.index').'" class="erp-btn-secondary">← Back</a>'])
@include('admin.hrm.partials.submodule-nav', ['section'=>'leave','current'=>'rules'])
<div class="erp-panel max-w-xl"><div class="erp-panel-body"><form method="POST" action="{{ $rule->exists?route('admin.hrm.leave.rules.update',$rule):route('admin.hrm.leave.rules.store') }}" class="space-y-3">@csrf @if($rule->exists)@method('PUT')@endif
<div><label class="erp-form-label">Factory</label><select name="factory_id" required class="erp-input !text-xs">@foreach($factories as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
<div><label class="erp-form-label">Leave Type</label><select name="leave_type_id" required class="erp-input !text-xs">@foreach($leaveTypes as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
<div><label class="erp-form-label">Worker Category</label><select name="worker_category_id" class="erp-input !text-xs"><option value="">Any</option>@foreach($workerCategories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
<div><label class="erp-form-label">Employment Type</label><select name="employment_type_id" class="erp-input !text-xs"><option value="">Any</option>@foreach($employmentTypes as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
<div><label class="erp-form-label">Min Tenure (days)</label><input type="number" name="min_tenure_days" value="{{ old('min_tenure_days',$rule->min_tenure_days) }}" class="erp-input !text-xs"></div>
<div><label class="erp-form-label">Gender</label><select name="gender" class="erp-input !text-xs"><option value="">Any</option>@foreach($genders as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
<button type="submit" class="erp-btn-primary">Save</button></form></div></div>
@endsection
