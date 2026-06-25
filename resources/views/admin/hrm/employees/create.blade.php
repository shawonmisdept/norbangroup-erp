@extends('layouts.admin')

@section('title', 'Enroll Employee')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.employees.index') }}" class="hover:text-brand">Employees</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Enroll</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'New Employee',
    'subtitle' => 'Complete all steps to enroll a worker',
])

@if(session('recruitment_application_id'))
    <div class="mb-4 bg-blue-50 border border-blue-200 rounded-sm p-3 text-xs text-blue-800">
        Pre-filled from recruitment application. Complete remaining official fields and save to finalize hire.
    </div>
@endif

@include('admin.hrm.employees._form', [
    'formAction' => route('admin.hrm.employees.store'),
    'formMethod' => 'POST',
])
@endsection
