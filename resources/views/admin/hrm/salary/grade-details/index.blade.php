@extends('layouts.admin')
@section('title', 'Grade Details')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Grade Details', 'actions' => auth()->user()->canManageSalarySubmodule('grade-details') ? '<a href="' . route('admin.hrm.salary.grade-details.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Detail</a>' : ''])
@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'grade-details'])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="erp-filter-bar">
            <div class="erp-filter-field w-48 sm:w-48">
                <label class="erp-form-label">Grade</label>
                <select name="salary_grade_id" class="erp-input !text-xs">
                    <option value="">All grades</option>
                    @foreach($grades as $id => $name)
                        <option value="{{ $id }}" {{ $filterGradeId === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary erp-filter-actions">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Grade</th>
                <th>Head</th>
                <th>Type</th>
                <th>Fixed</th>
                <th>Value / Formula</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($details as $d)
                <tr>
                    <td>{{ $d->grade?->name }}</td>
                    <td>{{ $d->salaryHead?->name }}</td>
                    <td class="text-xs font-semibold">{{ $d->detail_type }}</td>
                    <td class="text-xs">{{ $d->is_fixed ? 'Yes' : 'No' }}</td>
                    <td class="text-xs max-w-xs">
                        @if($d->detail_type === 'M')
                            <code class="text-[10px] break-all">{{ $d->formula }}</code>
                        @elseif($d->detail_type === 'P')
                            {{ number_format((float) $d->percentage, 2) }}%
                            @if($d->percentageOfHead) of {{ $d->percentageOfHead->name }} @endif
                        @else
                            ৳{{ number_format((float) $d->amount, 2) }}
                        @endif
                    </td>
                    <td class="text-right">
                        @include('admin.hrm.salary.partials.row-actions', [
                            'viewUrl' => route('admin.hrm.salary.grade-details.show', $d),
                            'editRoute' => auth()->user()->canManageSalarySubmodule('grade-details') ? route('admin.hrm.salary.grade-details.edit', $d) : null,
                            'destroyRoute' => auth()->user()->canManageSalarySubmodule('grade-details') ? route('admin.hrm.salary.grade-details.destroy', $d) : null,
                            'canManage' => auth()->user()->canManageSalarySubmodule('grade-details'),
                            'confirm' => 'Delete this grade detail?',
                        ])
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-8 text-gray-400">No grade details.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('admin.hrm.salary.partials.view-modal')
@endsection
