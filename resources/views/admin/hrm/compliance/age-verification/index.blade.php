@extends('layouts.admin')
@section('title', 'Age Verification')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Age Verification Report', 'actions' => ($factoryId?'<a href="'.route('admin.hrm.compliance.age-verification.export').'?factory_id='.$factoryId.'" class="erp-btn-secondary !text-xs">Export CSV</a>':'').'<a href="'.route('admin.hrm.compliance.hub').'" class="erp-btn-secondary ml-2">← Hub</a>'])
<div class="erp-panel mb-4"><div class="erp-panel-body">
<form method="GET" class="flex gap-3 items-end">
    <div><label class="erp-form-label">Factory</label><select name="factory_id" class="erp-input !text-xs">@foreach($factories as $id=>$n)<option value="{{ $id }}" {{ (int)$factoryId===(int)$id?'selected':'' }}>{{ $n }}</option>@endforeach</select></div>
    <button type="submit" class="erp-btn-primary">Load</button>
</form>
@if($factoryId)<p class="text-xs mt-3 {{ $nonCompliant > 0 ? 'text-red-600' : 'text-green-700' }}">{{ $nonCompliant }} non-compliant employee(s) found.</p>@endif
</div></div>
@if($factoryId)
<div class="erp-panel"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Code</th><th>Name</th><th>DOB</th><th>Age</th><th>Joining</th><th>Compliant</th></tr></thead>
<tbody>@forelse($rows as $row)
<tr class="{{ $row['compliant']==='No'?'bg-red-50':'' }}"><td>{{ $row['employee_code'] }}</td><td>{{ $row['employee_name'] }}</td><td>{{ $row['date_of_birth'] ?? '—' }}</td><td>{{ $row['age'] ?? '—' }}</td><td>{{ $row['joining_date'] ?? '—' }}</td><td>{{ $row['compliant'] }}</td></tr>
@empty<tr><td colspan="6" class="text-center py-8 text-gray-400">No active employees.</td></tr>@endforelse</tbody></table></div></div>
@endif
@endsection
