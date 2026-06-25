@extends('layouts.admin')

@section('title', 'Edit ' . $employee->name)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.employees.index') }}" class="hover:text-brand">Employees</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.employees.show', $employee) }}" class="hover:text-brand">{{ $employee->employee_code }}</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Edit</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Edit Employee',
    'subtitle' => $employee->employee_code . ' — ' . $employee->name,
])

@include('admin.hrm.employees._form', [
    'employee'   => $employee,
    'formAction' => route('admin.hrm.employees.update', $employee),
    'formMethod' => 'PUT',
])
@endsection
