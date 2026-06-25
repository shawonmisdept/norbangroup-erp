@extends('layouts.admin')
@section('title', 'New Roster')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'New Shift Roster', 'actions' => '<a href="' . route('admin.hrm.attendance.roster.index') . '" class="erp-btn-secondary">← Back</a>'])
<div class="erp-panel max-w-lg"><form method="POST" action="{{ route('admin.hrm.attendance.roster.store') }}" class="erp-panel-body space-y-4">@csrf
    <div><label class="erp-form-label">Factory</label><select name="factory_id" class="erp-input" required>@foreach($factories as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
    <div class="grid grid-cols-2 gap-4">
        <div><label class="erp-form-label">Start Date</label><input type="date" name="start_date" value="{{ old('start_date', $roster->start_date?->format('Y-m-d')) }}" class="erp-input" required></div>
        <div><label class="erp-form-label">End Date</label><input type="date" name="end_date" value="{{ old('end_date', $roster->end_date?->format('Y-m-d')) }}" class="erp-input" required></div>
    </div>
    <button type="submit" class="erp-btn-primary">Create Roster</button>
</form></div>
@endsection
