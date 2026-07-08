@extends('layouts.admin')
@section('title', ($pass->exists ? 'Edit' : 'New') . ' Gate Pass')
@section('admin-content')
@include('partials.erp.page-header', ['title' => ($pass->exists ? 'Edit' : 'New') . ' Gate Pass', 'actions' => '<a href="' . route('admin.hrm.rmg.gate-pass.index') . '" class="erp-btn-secondary">← Back</a>'])
<div class="erp-panel max-w-lg">
    <form method="POST" action="{{ $pass->exists ? route('admin.hrm.rmg.gate-pass.update', $pass) : route('admin.hrm.rmg.gate-pass.store') }}" class="erp-panel-body space-y-4">
        @csrf
        @if($pass->exists) @method('PUT') @endif
        <div><label class="erp-form-label">Factory</label><select name="factory_id" class="erp-input" required>@foreach($factories as $id=>$n)<option value="{{ $id }}" {{ (string) old('factory_id', $pass->factory_id) === (string) $id ? 'selected' : '' }}>{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Employee</label><select name="employee_id" class="erp-input" required><option value="">Select</option>@foreach($employees as $id=>$n)<option value="{{ $id }}" {{ (string) old('employee_id', $pass->employee_id) === (string) $id ? 'selected' : '' }}>{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Pass Date</label><input type="date" name="pass_date" value="{{ old('pass_date', $pass->pass_date?->toDateString()) }}" class="erp-input" required></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="erp-form-label">Out Time</label><input type="time" name="out_time" value="{{ old('out_time', \App\Support\TimeInput::formatForInput($pass->out_time)) }}" class="erp-input"></div>
            <div><label class="erp-form-label">Expected In</label><input type="time" name="expected_in_time" value="{{ old('expected_in_time', \App\Support\TimeInput::formatForInput($pass->expected_in_time)) }}" class="erp-input"></div>
        </div>
        <div><label class="erp-form-label">Destination</label><input type="text" name="destination" value="{{ old('destination', $pass->destination) }}" class="erp-input"></div>
        <div><label class="erp-form-label">Reason</label><textarea name="reason" rows="2" class="erp-input">{{ old('reason', $pass->reason) }}</textarea></div>
        <button type="submit" class="erp-btn-primary">{{ $pass->exists ? 'Update Gate Pass' : 'Submit Gate Pass' }}</button>
    </form>
</div>
@endsection
