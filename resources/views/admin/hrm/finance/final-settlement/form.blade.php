@extends('layouts.admin')
@section('title', 'New Final Settlement')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'New Final Settlement',
    'actions' => '<a href="' . route('admin.hrm.finance.final-settlement.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="erp-panel max-w-xl">
    <div class="erp-panel-body">
        <form method="POST" action="{{ route('admin.hrm.finance.final-settlement.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="erp-form-label">Employee <span class="text-red-500">*</span></label>
                <select name="employee_id" class="erp-input" required>
                    <option value="">Select separated employee</option>
                    @foreach($employees as $id => $label)
                        <option value="{{ $id }}" {{ (string) old('employee_id', $selectedEmployeeId ?? '') === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-gray-400">Only resigned / terminated employees without an existing F&F record.</p>
                @error('employee_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="erp-form-label">Last Working Day <span class="text-red-500">*</span></label>
                <input type="date" name="last_working_day" value="{{ old('last_working_day', $settlement->last_working_day?->format('Y-m-d')) }}" class="erp-input" required>
                @error('last_working_day')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="erp-btn-primary">Create Draft</button>
        </form>
    </div>
</div>
@endsection
