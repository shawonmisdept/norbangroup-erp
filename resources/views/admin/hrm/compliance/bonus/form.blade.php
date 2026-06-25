@extends('layouts.admin')
@section('title', 'New Bonus Run')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'New Festival Bonus Run', 'actions' => '<a href="'.route('admin.hrm.compliance.bonus.index').'" class="erp-btn-secondary">← Back</a>'])
<div class="erp-panel max-w-xl"><div class="erp-panel-body"><form method="POST" action="{{ route('admin.hrm.compliance.bonus.store') }}" class="space-y-3">@csrf
<div><label class="erp-form-label">Factory</label><select name="factory_id" required class="erp-input !text-xs">@foreach($factories as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
<div><label class="erp-form-label">Bonus Type</label><select name="bonus_type" class="erp-input !text-xs">@foreach($types as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
<div><label class="erp-form-label">Year</label><input type="number" name="year" value="{{ old('year',$run->year) }}" required class="erp-input !text-xs"></div>
<div><label class="erp-form-label">Bonus Date (optional)</label><input type="date" name="bonus_date" class="erp-input !text-xs"></div>
<button type="submit" class="erp-btn-primary">Create Run</button></form></div></div>
@endsection
