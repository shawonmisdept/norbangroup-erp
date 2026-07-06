@extends('layouts.admin')

@section('title', 'Employees — ' . config('app.name'))

@section('breadcrumbs')
    <span class="text-gray-600 font-medium">HRM</span>
    <span>/</span>
    <span class="text-gray-800 font-medium">Employees</span>
@endsection

@section('admin-content')

@include('partials.erp.page-header', [
    'title' => 'Employee Registry',
    'subtitle' => 'Enrolled workers across all factory units',
    'actions' => auth()->user()->canManageEmployeeSubmodule('employees')
        ? '<a href="' . route('admin.hrm.employees.create') . '" class="erp-btn-primary">+ Add Employee</a>'
        : '',
])

<div class="erp-panel mb-4" x-data="employeeIndexFilters()">
    <div class="erp-panel-body space-y-4">
        <form method="GET"
              action="{{ route('admin.hrm.employees.index') }}"
              x-ref="filterForm"
              class="space-y-4">
            @include('admin.hrm.employees.partials.filters')
        </form>
    </div>
</div>

@include('admin.hrm.employees.partials.table')
@endsection
