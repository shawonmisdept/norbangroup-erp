@extends('layouts.admin')

@section('title', $posting->exists ? 'Edit Posting' : 'New Posting')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.recruitment.postings.index') }}" class="hover:text-brand">Job Postings</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $posting->exists ? 'Edit' : 'New' }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $posting->exists ? 'Edit Job Posting' : 'New Job Posting',
    'actions' => '<a href="' . route('admin.hrm.recruitment.postings.index') . '" class="erp-btn-secondary">← Back</a>',
])

<form method="POST" action="{{ $posting->exists ? route('admin.hrm.recruitment.postings.update', $posting) : route('admin.hrm.recruitment.postings.store') }}" class="max-w-4xl erp-panel">
    @csrf
    @if($posting->exists) @method('PUT') @endif
    <div class="erp-panel-body space-y-6">
        <div>
            <h2 class="text-xs font-semibold uppercase text-gray-500 mb-3">Basic Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="erp-form-label">Title *</label>
                    <input type="text" name="title" required value="{{ old('title', $posting->title) }}" class="erp-input !text-xs">
                </div>
                <div>
                    <label class="erp-form-label">Factory *</label>
                    <select name="factory_id" required class="erp-input !text-xs">
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ (string) old('factory_id', $defaultFactoryId) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Status *</label>
                    <select name="status" class="erp-input !text-xs">
                        @foreach($statuses as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $posting->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Department</label>
                    <select name="department_id" class="erp-input !text-xs">
                        <option value="">—</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (string) old('department_id', $posting->department_id) === (string) $dept->id ? 'selected' : '' }}>{{ $dept->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Designation</label>
                    <select name="designation_id" class="erp-input !text-xs">
                        <option value="">—</option>
                        @foreach($designations as $des)
                            <option value="{{ $des->id }}" {{ (string) old('designation_id', $posting->designation_id) === (string) $des->id ? 'selected' : '' }}>{{ $des->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Worker Category</label>
                    <select name="worker_category_id" class="erp-input !text-xs">
                        <option value="">—</option>
                        @foreach($workerCategories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) old('worker_category_id', $posting->worker_category_id) === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Slots *</label>
                    <input type="number" name="slots" min="1" required value="{{ old('slots', $posting->slots ?? 1) }}" class="erp-input !text-xs">
                </div>
                <div>
                    <label class="erp-form-label">Closing Date</label>
                    <input type="date" name="closes_at" value="{{ old('closes_at', $posting->closes_at?->format('Y-m-d')) }}" class="erp-input !text-xs">
                </div>
                <div class="col-span-2">
                    <label class="inline-flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                        <input type="checkbox" name="salary_negotiable" value="1" id="salary_negotiable" class="rounded border-gray-300"
                            {{ old('salary_negotiable', $posting->salary_negotiable) ? 'checked' : '' }}>
                        Salary is negotiable
                    </label>
                </div>
                <div class="col-span-2" id="salary_amount_wrap">
                    <label class="erp-form-label" for="salary_text" id="salary_amount_label">Salary Amount</label>
                    <p class="text-[10px] text-gray-400 mb-1.5" id="salary_amount_hint">
                        e.g. Tk. 25,000 – 30,000 (Monthly). Required when salary is not negotiable.
                    </p>
                    <input
                        type="text"
                        name="salary_text"
                        id="salary_text"
                        value="{{ old('salary_text', strip_tags((string) $posting->salary_text)) }}"
                        class="erp-input !text-xs w-full"
                        placeholder="Tk. 25,000 – 30,000 (Monthly)"
                    >
                    @error('salary_text')
                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-xs font-semibold uppercase text-gray-500 mb-3">Public Job Page Sections</h2>
            <p class="text-[11px] text-gray-400 mb-3">These four sections appear as tabs on the careers job details page.</p>
            <div class="grid grid-cols-2 gap-4">
                @include('partials.admin.rich-text-field', [
                    'name' => 'requirements',
                    'label' => 'Requirements',
                    'value' => $posting->requirements,
                    'hint' => 'Education, experience, and other eligibility criteria.',
                ])
                @include('partials.admin.rich-text-field', [
                    'name' => 'responsibilities',
                    'label' => 'Responsibilities',
                    'value' => $posting->responsibilities,
                ])
                @include('partials.admin.rich-text-field', [
                    'name' => 'skills_expertise',
                    'label' => 'Skills & Expertise',
                    'value' => $posting->skills_expertise,
                ])
                @include('partials.admin.rich-text-field', [
                    'name' => 'employment_status',
                    'label' => 'Employment Status',
                    'value' => $posting->employment_status,
                    'hint' => 'Full-time, contract, probation period, shift details, etc.',
                ])
            </div>
        </div>

        <button type="submit" class="erp-btn-primary">Save Posting</button>
    </div>
</form>

@include('partials.admin.rich-text-editor')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const negotiable = document.getElementById('salary_negotiable');
    const amountInput = document.getElementById('salary_text');
    const amountHint = document.getElementById('salary_amount_hint');

    if (!negotiable || !amountInput) return;

    const syncSalaryField = () => {
        const isNegotiable = negotiable.checked;
        amountInput.required = !isNegotiable;
        amountInput.placeholder = isNegotiable
            ? 'Optional — e.g. Tk. 25,000 – 30,000 (Monthly)'
            : 'Tk. 25,000 – 30,000 (Monthly)';
        document.getElementById('salary_amount_label').textContent = isNegotiable
            ? 'Salary Amount (optional)'
            : 'Salary Amount *';
        amountHint.textContent = isNegotiable
            ? 'Optional — leave blank to show only “Negotiable” on the public page.'
            : 'e.g. Tk. 25,000 – 30,000 (Monthly). Required when salary is not negotiable.';
    };

    negotiable.addEventListener('change', syncSalaryField);
    syncSalaryField();
});
</script>
@endpush
@endsection
