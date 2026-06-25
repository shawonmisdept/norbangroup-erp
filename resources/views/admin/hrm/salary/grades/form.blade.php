@extends('layouts.admin')
@section('title', 'Salary Grade')
@section('admin-content')
@include('partials.erp.page-header', ['title'=>($grade->exists?'Edit':'Add').' Grade','actions'=>'<a href="'.route('admin.hrm.salary.grades.index').'" class="erp-btn-secondary">← Back</a>'])
@include('admin.hrm.partials.submodule-nav', ['section'=>'salary','current'=>'grades'])
<div class="erp-panel max-w-xl"><div class="erp-panel-body"><form method="POST" action="{{ $grade->exists?route('admin.hrm.salary.grades.update',$grade):route('admin.hrm.salary.grades.store') }}" class="space-y-3">@csrf @if($grade->exists)@method('PUT')@endif
<select name="factory_id" required class="erp-input !text-xs">@foreach($factories as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select>
<input name="code" value="{{ old('code',$grade->code) }}" placeholder="Code" required class="erp-input !text-xs">
<input name="name" value="{{ old('name',$grade->name) }}" placeholder="Name" required class="erp-input !text-xs">
<textarea name="description" placeholder="Description" class="erp-input !text-xs">{{ old('description',$grade->description) }}</textarea>
<button type="submit" class="erp-btn-primary">Save</button></form></div></div>
@endsection
