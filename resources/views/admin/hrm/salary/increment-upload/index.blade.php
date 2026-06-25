@extends('layouts.admin')
@section('title', 'Increment Upload')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Increment Upload',
    'subtitle' => 'Bulk CSV increment — set new gross or apply named rule',
    'actions' => '<a href="' . route('admin.hrm.salary.increment-upload.template') . '" class="erp-btn-secondary">Download Template</a>',
])
@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'increment-upload'])

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase tracking-wide text-gray-700">Upload CSV</h2></div>
        <div class="erp-panel-body">
            @if(auth()->user()->canManageSalarySubmodule('increment-upload'))
            <form method="POST" action="{{ route('admin.hrm.salary.increment-upload.store') }}" enctype="multipart/form-data" class="space-y-3">
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
                <button type="submit" class="erp-btn-primary">Upload & Apply</button>
            </form>
            @else
            <p class="text-sm text-gray-500">You do not have permission to upload increments.</p>
            @endif
        </div>
    </div>

    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase tracking-wide text-gray-700">Column Guide</h2></div>
        <div class="erp-panel-body text-xs text-gray-600 space-y-2">
            <p><code class="text-[10px] bg-gray-100 px-1">employee_code</code> — required</p>
            <p><code class="text-[10px] bg-gray-100 px-1">new_gross</code> — set exact new gross (grade heads recalculate)</p>
            <p><code class="text-[10px] bg-gray-100 px-1">rule_name</code> — or apply existing increment rule by name</p>
            <p class="text-gray-400 pt-2">Provide <strong>either</strong> new_gross <strong>or</strong> rule_name per row. Salary staff with grade only.</p>
        </div>
    </div>
</div>
@endsection
