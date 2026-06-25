@extends('layouts.admin')

@section('title', 'New Separation')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.separations.index') }}" class="hover:text-brand">Separations</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">New</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Initiate Separation',
    'subtitle' => 'Resignation, termination, retirement, layoff or absconding',
    'actions' => '<a href="' . route('admin.hrm.separations.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="erp-panel max-w-2xl">
    <div class="erp-panel-body">
        <form method="POST" action="{{ route('admin.hrm.separations.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="erp-form-label">Employee <span class="text-red-500">*</span></label>
                <select name="employee_id" class="erp-input" required>
                    <option value="">Select employee</option>
                    @foreach($employees as $id => $label)
                        <option value="{{ $id }}" {{ (string) old('employee_id', $selectedEmployee) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('employee_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="erp-form-label">Separation Type <span class="text-red-500">*</span></label>
                <select name="separation_type" class="erp-input" required>
                    @foreach($separationTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('separation_type', $separation->separation_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('separation_type')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Application Date <span class="text-red-500">*</span></label>
                    <input type="date" name="application_date" value="{{ old('application_date', $separation->application_date?->format('Y-m-d')) }}" class="erp-input" required>
                    @error('application_date')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="erp-form-label">Last Working Day <span class="text-red-500">*</span></label>
                    <input type="date" name="last_working_day" value="{{ old('last_working_day', $separation->last_working_day?->format('Y-m-d')) }}" class="erp-input" required>
                    @error('last_working_day')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="erp-form-label">Notice Period (days)</label>
                <input type="number" name="notice_period_days" value="{{ old('notice_period_days') }}" min="0" max="365" class="erp-input" placeholder="Optional">
            </div>
            <div>
                <label class="erp-form-label">Reason</label>
                <textarea name="reason" rows="3" class="erp-input" placeholder="Reason for separation…">{{ old('reason') }}</textarea>
            </div>
            <div>
                <label class="erp-form-label">HR Remarks</label>
                <textarea name="remarks" rows="2" class="erp-input" placeholder="Internal notes…">{{ old('remarks') }}</textarea>
            </div>
            <div>
                <label class="erp-form-label">Attachment</label>
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="erp-input !py-1.5">
                <p class="text-[11px] text-gray-400 mt-1">Resignation letter, termination notice, etc.</p>
            </div>
            <button type="submit" class="erp-btn-primary">Submit Separation Request</button>
        </form>
    </div>
</div>
@endsection
