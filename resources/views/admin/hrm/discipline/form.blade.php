@extends('layouts.admin')

@section('title', 'New Disciplinary Record')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.discipline.index') }}" class="hover:text-brand">Disciplinary</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">New</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Record Disciplinary Action',
    'subtitle' => 'Warning, suspension or misconduct log',
    'actions' => '<a href="' . route('admin.hrm.discipline.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.hrm.discipline.store') }}" class="erp-panel">
        @csrf
        <div class="erp-panel-body space-y-4">
            <div>
                <label class="erp-form-label">Employee *</label>
                <select name="employee_id" required class="erp-input !text-xs">
                    <option value="">Select employee…</option>
                    @foreach($employees as $id => $label)
                        <option value="{{ $id }}" {{ (int) old('employee_id', $selectedEmployee) === (int) $id ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('employee_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Action Type *</label>
                    <select name="action_type" id="action_type" required class="erp-input !text-xs">
                        @foreach($actionTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('action_type', $record->action_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Incident Date *</label>
                    <input type="date" name="incident_date" required value="{{ old('incident_date', $record->incident_date?->format('Y-m-d')) }}" class="erp-input !text-xs">
                </div>
            </div>
            <div id="suspension-fields" class="grid grid-cols-2 gap-4 hidden">
                <div>
                    <label class="erp-form-label">Suspension From</label>
                    <input type="date" name="suspension_from" value="{{ old('suspension_from') }}" class="erp-input !text-xs">
                </div>
                <div>
                    <label class="erp-form-label">Suspension To</label>
                    <input type="date" name="suspension_to" value="{{ old('suspension_to') }}" class="erp-input !text-xs">
                </div>
            </div>
            <div>
                <label class="erp-form-label">Description *</label>
                <textarea name="description" rows="4" required class="erp-input !text-xs" placeholder="Incident details…">{{ old('description') }}</textarea>
                @error('description')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="erp-form-label">Action Taken</label>
                <textarea name="action_taken" rows="2" class="erp-input !text-xs" placeholder="Management decision…">{{ old('action_taken') }}</textarea>
            </div>
            <button type="submit" class="erp-btn-primary">Save Record</button>
        </div>
    </form>
</div>

<script>
    const typeSelect = document.getElementById('action_type');
    const suspensionFields = document.getElementById('suspension-fields');
    function toggleSuspension() {
        suspensionFields.classList.toggle('hidden', typeSelect.value !== 'suspension');
    }
    typeSelect.addEventListener('change', toggleSuspension);
    toggleSuspension();
</script>
@endsection
