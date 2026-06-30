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
    'actions' => '<a href="' . route('admin.hrm.recruitment.postings.index') . '" class="erp-btn-secondary">← Back</a>'
        . (!$posting->exists ? ' <a href="' . route('admin.hrm.recruitment.postings.bulk.create') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Bulk Create</a>' : ''),
])

@if(!$posting->exists && ($templates ?? []) !== [])
    <div class="erp-panel mb-4">
        <div class="erp-panel-body">
            <p class="text-xs text-gray-500 mb-2">Start from a template:</p>
            <div class="flex flex-wrap gap-2">
                @foreach($templates as $key => $tpl)
                    <a href="{{ route('admin.hrm.recruitment.postings.create', ['template' => $key]) }}" class="erp-btn-sm-secondary">{{ $tpl['label'] ?? $key }}</a>
                @endforeach
            </div>
        </div>
    </div>
@endif

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
                <div class="col-span-2">
                    <label class="erp-form-label">Title (Bengali)</label>
                    <input type="text" name="title_bn" value="{{ old('title_bn', $posting->title_bn) }}" class="erp-input !text-xs" placeholder="Optional Bengali title">
                </div>
                <div>
                    <label class="erp-form-label">Factory *</label>
                    <select name="factory_id" id="factory_id" required class="erp-input !text-xs">
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
                    <select name="department_id" id="department_id" class="erp-input !text-xs">
                        <option value="">—</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (string) old('department_id', $posting->department_id) === (string) $dept->id ? 'selected' : '' }}>{{ $dept->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Designation</label>
                    <select name="designation_id" id="designation_id" class="erp-input !text-xs">
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
                    <label class="erp-form-label">Shift</label>
                    <select name="shift_type" class="erp-input !text-xs">
                        <option value="">—</option>
                        @foreach($shiftTypes as $val => $label)
                            <option value="{{ $val }}" {{ old('shift_type', $posting->shift_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Min Age</label>
                    <input type="number" name="min_age" min="14" max="70" value="{{ old('min_age', $posting->min_age) }}" class="erp-input !text-xs">
                </div>
                <div>
                    <label class="erp-form-label">Max Age</label>
                    <input type="number" name="max_age" min="14" max="70" value="{{ old('max_age', $posting->max_age) }}" class="erp-input !text-xs">
                </div>
                <div>
                    <label class="erp-form-label">Required Gender</label>
                    <select name="required_gender" class="erp-input !text-xs">
                        <option value="">Any</option>
                        @foreach($postingGenders as $val => $label)
                            <option value="{{ $val }}" {{ old('required_gender', $posting->required_gender) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Slots *</label>
                    <input type="number" name="slots" min="{{ max(1, (int) $posting->openings_filled) }}" required value="{{ old('slots', $posting->slots ?? 1) }}" class="erp-input !text-xs">
                    @if($posting->exists && $posting->openings_filled > 0)
                        <p class="text-[10px] text-gray-400 mt-1">{{ $posting->openings_filled }} already filled</p>
                    @endif
                </div>
                <div>
                    <label class="erp-form-label">Closing Date</label>
                    <input type="date" name="closes_at" value="{{ old('closes_at', $posting->closes_at?->format('Y-m-d')) }}" class="erp-input !text-xs">
                </div>
                <div class="col-span-2 flex flex-wrap gap-4">
                    <label class="inline-flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                        <input type="checkbox" name="salary_negotiable" value="1" id="salary_negotiable" class="rounded border-gray-300"
                            {{ old('salary_negotiable', $posting->salary_negotiable) ? 'checked' : '' }}>
                        Salary is negotiable
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                        <input type="checkbox" name="is_internal" value="1" class="rounded border-gray-300"
                            {{ old('is_internal', $posting->is_internal) ? 'checked' : '' }}>
                        Internal only (hide from careers portal)
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                        <input type="checkbox" name="rehire_eligible" value="1" class="rounded border-gray-300"
                            {{ old('rehire_eligible', $posting->rehire_eligible) ? 'checked' : '' }}>
                        Former employees eligible
                    </label>
                </div>
                <div class="col-span-2" id="salary_amount_wrap">
                    <label class="erp-form-label" for="salary_text" id="salary_amount_label">Salary Amount</label>
                    <p class="text-[10px] text-gray-400 mb-1.5" id="salary_amount_hint">e.g. Tk. 25,000 – 30,000 (Monthly)</p>
                    <input type="text" name="salary_text" id="salary_text" value="{{ old('salary_text', strip_tags((string) $posting->salary_text)) }}" class="erp-input !text-xs w-full">
                    @error('salary_text')<p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-2">
                    <label class="erp-form-label">SEO Meta Description</label>
                    <input type="text" name="meta_description" maxlength="500" value="{{ old('meta_description', $posting->meta_description) }}" class="erp-input !text-xs" placeholder="Short summary for search engines and social sharing">
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-xs font-semibold uppercase text-gray-500 mb-3">Overview</h2>
            <div class="space-y-4">
                @include('partials.admin.rich-text-field', ['name' => 'description', 'label' => 'Description', 'value' => $posting->description])
                @include('partials.admin.rich-text-field', ['name' => 'description_bn', 'label' => 'Description (Bengali)', 'value' => $posting->description_bn])
            </div>
        </div>

        <div>
            <h2 class="text-xs font-semibold uppercase text-gray-500 mb-3">Public Job Page Sections</h2>
            <div class="grid grid-cols-2 gap-4">
                @include('partials.admin.rich-text-field', ['name' => 'requirements', 'label' => 'Requirements', 'value' => $posting->requirements])
                @include('partials.admin.rich-text-field', ['name' => 'responsibilities', 'label' => 'Responsibilities', 'value' => $posting->responsibilities])
                @include('partials.admin.rich-text-field', ['name' => 'skills_expertise', 'label' => 'Skills & Expertise', 'value' => $posting->skills_expertise])
                @include('partials.admin.rich-text-field', ['name' => 'employment_status', 'label' => 'Employment Status', 'value' => $posting->employment_status])
                @include('partials.admin.rich-text-field', ['name' => 'benefits', 'label' => 'Benefits', 'value' => $posting->benefits])
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
    const factorySelect = document.getElementById('factory_id');
    const deptSelect = document.getElementById('department_id');
    const desSelect = document.getElementById('designation_id');
    const formOptionsUrl = @json($formOptionsUrl ?? null);

    const syncSalaryField = () => {
        if (!negotiable || !amountInput) return;
        const isNegotiable = negotiable.checked;
        amountInput.required = !isNegotiable;
        document.getElementById('salary_amount_label').textContent = isNegotiable ? 'Salary Amount (optional)' : 'Salary Amount *';
    };

    const refillSelect = (select, items, selected) => {
        select.innerHTML = '<option value="">—</option>';
        Object.entries(items).forEach(([id, label]) => {
            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = label;
            if (String(selected) === String(id)) opt.selected = true;
            select.appendChild(opt);
        });
    };

    const loadFactoryOptions = async (factoryId) => {
        if (!formOptionsUrl || !factoryId) return;
        const res = await fetch(formOptionsUrl + '?factory_id=' + encodeURIComponent(factoryId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) return;
        const data = await res.json();
        refillSelect(deptSelect, data.departments || {}, '');
        refillSelect(desSelect, data.designations || {}, '');
    };

    negotiable?.addEventListener('change', syncSalaryField);
    syncSalaryField();

    factorySelect?.addEventListener('change', () => loadFactoryOptions(factorySelect.value));
});
</script>
@endpush
@endsection
