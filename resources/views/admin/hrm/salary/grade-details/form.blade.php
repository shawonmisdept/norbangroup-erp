@extends('layouts.admin')
@section('title', 'Grade Detail')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Grade Detail', 'actions' => '<a href="' . route('admin.hrm.salary.grade-details.index') . '" class="erp-btn-secondary">← Back</a>'])
@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'grade-details'])

@php
    $detailType = old('detail_type', $detail->detail_type ?? 'F');
@endphp

<div class="erp-panel max-w-3xl">
    <div class="erp-panel-body">
        <form method="POST"
              action="{{ $detail->exists ? route('admin.hrm.salary.grade-details.update', $detail) : route('admin.hrm.salary.grade-details.store') }}"
              class="space-y-4"
              id="grade-detail-form">
            @csrf
            @if($detail->exists) @method('PUT') @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Grade</label>
                    <select name="salary_grade_id" required class="erp-input !text-xs">
                        <option value="">Grade…</option>
                        @foreach($grades as $id => $n)
                            <option value="{{ $id }}" {{ (string) old('salary_grade_id', $detail->salary_grade_id) === (string) $id ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Salary Head</label>
                    <select name="salary_head_id" required class="erp-input !text-xs">
                        <option value="">Head…</option>
                        @foreach($heads as $id => $n)
                            <option value="{{ $id }}" {{ (string) old('salary_head_id', $detail->salary_head_id) === (string) $id ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Type (M / F / P)</label>
                    <select name="detail_type" id="detail_type" class="erp-input !text-xs">
                        @foreach($detailTypes as $k => $label)
                            <option value="{{ $k }}" {{ $detailType === $k ? 'selected' : '' }}>{{ $k }} — {{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">M=Formula, F=Fixed, P=Percentage</p>
                </div>
                <div>
                    <label class="erp-form-label">Is Fixed</label>
                    <select name="is_fixed" class="erp-input !text-xs">
                        <option value="1" {{ old('is_fixed', $detail->is_fixed) ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ ! old('is_fixed', $detail->is_fixed) ? 'selected' : '' }}>No</option>
                    </select>
                </div>
            </div>

            <div id="field-fixed" class="{{ $detailType === 'F' ? '' : 'hidden' }}">
                <label class="erp-form-label">Fixed Amount (৳)</label>
                <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $detail->amount) }}" class="erp-input !text-xs">
            </div>

            <div id="field-percentage" class="{{ $detailType === 'P' ? '' : 'hidden' }} space-y-3">
                <div>
                    <label class="erp-form-label">Percentage (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="percentage" value="{{ old('percentage', $detail->percentage) }}" class="erp-input !text-xs">
                </div>
                <div>
                    <label class="erp-form-label">Parent Head</label>
                    <select name="percentage_of_head_id" class="erp-input !text-xs">
                        <option value="">Select parent…</option>
                        @foreach($heads as $id => $n)
                            <option value="{{ $id }}" {{ (string) old('percentage_of_head_id', $detail->percentage_of_head_id) === (string) $id ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="field-formula" class="{{ $detailType === 'M' ? '' : 'hidden' }}">
                <label class="erp-form-label">Formula</label>
                <textarea name="formula" rows="3" class="erp-input !text-xs font-mono text-[11px]" placeholder="(<GROSS>-(<MEDICAL>+<FOOD ALLOWANCE>+<CONVEYANCE>))/1.4">{{ old('formula', $detail->formula) }}</textarea>
                <p class="text-[11px] text-gray-400 mt-1">Use &lt;HEAD CODE&gt; placeholders, e.g. &lt;GROSS&gt;, &lt;BASIC&gt;</p>
            </div>

            <button type="submit" class="erp-btn-primary">Save</button>
        </form>
    </div>
</div>

<script>
(function () {
    const typeSelect = document.getElementById('detail_type');
    const fields = {
        F: document.getElementById('field-fixed'),
        P: document.getElementById('field-percentage'),
        M: document.getElementById('field-formula'),
    };
    function toggle() {
        const t = typeSelect.value;
        Object.entries(fields).forEach(([k, el]) => el.classList.toggle('hidden', k !== t));
    }
    typeSelect.addEventListener('change', toggle);
})();
</script>
@endsection
