@extends('layouts.admin')
@section('title', ($transfer->exists ? 'Edit' : 'New') . ' Worker Transfer')
@section('admin-content')
@include('partials.erp.page-header', ['title' => ($transfer->exists ? 'Edit' : 'New') . ' Worker Transfer', 'actions' => '<a href="' . route('admin.hrm.rmg.worker-transfer.index') . '" class="erp-btn-secondary">← Back</a>'])
<div class="erp-panel max-w-lg">
    <form method="POST" action="{{ $transfer->exists ? route('admin.hrm.rmg.worker-transfer.update', $transfer) : route('admin.hrm.rmg.worker-transfer.store') }}" class="erp-panel-body space-y-4">
        @csrf
        @if($transfer->exists) @method('PUT') @endif
        <div><label class="erp-form-label">Factory</label><select name="factory_id" class="erp-input" required>@foreach($factories as $id=>$n)<option value="{{ $id }}" {{ (string) old('factory_id', $transfer->factory_id) === (string) $id ? 'selected' : '' }}>{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Employee</label><select name="employee_id" class="erp-input" required><option value="">Select</option>@foreach($employees as $id=>$n)<option value="{{ $id }}" {{ (string) old('employee_id', $transfer->employee_id) === (string) $id ? 'selected' : '' }}>{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">To Factory</label><select name="to_factory_id" class="erp-input"><option value="">—</option>@foreach($factories as $id=>$n)<option value="{{ $id }}" {{ (string) old('to_factory_id', $transfer->to_factory_id) === (string) $id ? 'selected' : '' }}>{{ $n }}</option>@endforeach</select></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="erp-form-label">To Line</label><select name="to_line_id" class="erp-input"><option value="">—</option>@foreach($lines as $id=>$n)<option value="{{ $id }}" {{ (string) old('to_line_id', $transfer->to_line_id) === (string) $id ? 'selected' : '' }}>{{ $n }}</option>@endforeach</select></div>
            <div><label class="erp-form-label">To Floor</label><select name="to_floor_id" class="erp-input"><option value="">—</option>@foreach($floors as $id=>$n)<option value="{{ $id }}" {{ (string) old('to_floor_id', $transfer->to_floor_id) === (string) $id ? 'selected' : '' }}>{{ $n }}</option>@endforeach</select></div>
        </div>
        <div><label class="erp-form-label">To Building</label><select name="to_building_id" class="erp-input"><option value="">—</option>@foreach($buildings as $id=>$n)<option value="{{ $id }}" {{ (string) old('to_building_id', $transfer->to_building_id) === (string) $id ? 'selected' : '' }}>{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Effective Date</label><input type="date" name="effective_date" value="{{ old('effective_date', $transfer->effective_date?->toDateString()) }}" class="erp-input" required></div>
        <div><label class="erp-form-label">Reason</label><textarea name="reason" rows="2" class="erp-input">{{ old('reason', $transfer->reason) }}</textarea></div>
        <button type="submit" class="erp-btn-primary">{{ $transfer->exists ? 'Update Transfer' : 'Submit Transfer' }}</button>
    </form>
</div>
@endsection
