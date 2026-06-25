@extends('layouts.admin')
@section('title', 'Upload Salary')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Upload Salary',
    'subtitle' => 'Bulk CSV upload for employee salary structures',
    'actions' => '<a href="' . route('admin.hrm.salary.upload.template') . '" class="erp-btn-secondary">Download Template</a>',
])
@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'upload'])

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase tracking-wide text-gray-700">Upload CSV</h2></div>
        <div class="erp-panel-body">
            @if(auth()->user()->canManageSalarySubmodule('upload'))
            <form method="POST" action="{{ route('admin.hrm.salary.upload.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" required class="erp-input !text-xs">
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">CSV File</label>
                    <input type="file" name="file" accept=".csv,text/csv" required class="erp-input !text-xs">
                </div>
                <button type="submit" class="erp-btn-primary">Upload & Import</button>
            </form>
            @else
            <p class="text-sm text-gray-500">You do not have permission to upload salary data.</p>
            @endif
        </div>
    </div>

    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase tracking-wide text-gray-700">Column Guide</h2></div>
        <div class="erp-panel-body text-xs text-gray-600 space-y-2">
            <p><code class="text-[10px] bg-gray-100 px-1">employee_code</code> — required, must exist in factory</p>
            <p><code class="text-[10px] bg-gray-100 px-1">pay_type</code> — <strong>salary</strong> (grade-based) or <strong>wages</strong> (daily)</p>
            <p class="font-medium text-gray-700 pt-1">For salary staff:</p>
            <p><code class="text-[10px] bg-gray-100 px-1">salary_grade_code</code> + <code class="text-[10px] bg-gray-100 px-1">gross_salary</code> — heads auto-calculate from grade rules</p>
            <p class="font-medium text-gray-700 pt-1">For wage workers:</p>
            <p><code class="text-[10px] bg-gray-100 px-1">daily_wage</code> — required; optional <code class="text-[10px] bg-gray-100 px-1">hra, medical, conveyance, other_allowance</code></p>
            <p><code class="text-[10px] bg-gray-100 px-1">payment_method</code> — bank or cash</p>
            <p class="text-gray-400 pt-2">Existing employee salary records are updated; new ones are created.</p>
        </div>
    </div>
</div>
@endsection
