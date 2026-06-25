@extends('layouts.admin')
@section('title', 'Maternity Rule')
@section('admin-content')
@include('partials.erp.page-header', ['title'=>($rule->exists?'Edit':'Add').' Maternity Rule','actions'=>'<a href="'.route('admin.hrm.leave.maternity-rules.index').'" class="erp-btn-secondary">← Back</a>'])
@include('admin.hrm.partials.submodule-nav', ['section'=>'leave','current'=>'maternity-rules'])
<div class="erp-panel max-w-xl"><div class="erp-panel-body"><form method="POST" action="{{ $rule->exists?route('admin.hrm.leave.maternity-rules.update',$rule):route('admin.hrm.leave.maternity-rules.store') }}" class="space-y-3 grid grid-cols-2 gap-3">@csrf @if($rule->exists)@method('PUT')@endif
<div class="col-span-2"><label class="erp-form-label">Factory</label><select name="factory_id" required class="erp-input !text-xs">@foreach($factories as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
<div><label class="erp-form-label">Total Weeks</label><input type="number" name="total_weeks" value="{{ old('total_weeks',$rule->total_weeks) }}" class="erp-input !text-xs"></div>
<div><label class="erp-form-label">Paid Weeks</label><input type="number" name="paid_weeks" value="{{ old('paid_weeks',$rule->paid_weeks) }}" class="erp-input !text-xs"></div>
<div><label class="erp-form-label">Unpaid Weeks</label><input type="number" name="unpaid_weeks" value="{{ old('unpaid_weeks',$rule->unpaid_weeks) }}" class="erp-input !text-xs"></div>
<div><label class="erp-form-label">Min Service Days</label><input type="number" name="min_service_days" value="{{ old('min_service_days',$rule->min_service_days) }}" class="erp-input !text-xs"></div>
<div class="col-span-2"><button type="submit" class="erp-btn-primary">Save</button></div></form></div></div>
@endsection
