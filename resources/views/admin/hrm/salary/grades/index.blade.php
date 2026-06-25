@extends('layouts.admin')
@section('title', 'Salary Grades')
@section('admin-content')
@include('partials.erp.page-header', ['title'=>'Grade','actions'=>auth()->user()->canManageSalarySubmodule('grades')?'<a href="'.route('admin.hrm.salary.grades.create').'" class="erp-btn-primary !py-2 !px-4 text-xs">Add Grade</a>':''])
@include('admin.hrm.partials.submodule-nav', ['section'=>'salary','current'=>'grades'])
<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Factory</th>
                <th>Details</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grades as $grade)
                <tr>
                    <td><code class="text-xs">{{ $grade->code }}</code></td>
                    <td>{{ $grade->name }}</td>
                    <td class="text-xs">{{ $grade->factory?->name }}</td>
                    <td>{{ $grade->details_count }}</td>
                    <td class="text-right">
                        @include('admin.hrm.salary.partials.row-actions', [
                            'viewUrl' => route('admin.hrm.salary.grades.show', $grade),
                            'editRoute' => auth()->user()->canManageSalarySubmodule('grades') ? route('admin.hrm.salary.grades.edit', $grade) : null,
                            'destroyRoute' => auth()->user()->canManageSalarySubmodule('grades') ? route('admin.hrm.salary.grades.destroy', $grade) : null,
                            'canManage' => auth()->user()->canManageSalarySubmodule('grades'),
                            'confirm' => 'Delete grade "' . $grade->name . '" and all its details?',
                        ])
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-8 text-gray-400">No grades.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($grades->hasPages())
        <div class="px-4 py-3 border-t">{{ $grades->links() }}</div>
    @endif
</div>

@include('admin.hrm.salary.partials.view-modal')
@endsection
