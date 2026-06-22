@extends('layouts.admin')

@section('title', 'Add Role')

@section('breadcrumbs')
    <a href="{{ route('admin.roles.index') }}" class="hover:text-brand">Roles</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">New</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', ['title' => 'New Role', 'subtitle' => 'Define module access permissions'])

<form method="POST" action="{{ route('admin.roles.store') }}" class="max-w-4xl">
    @csrf
    <div class="erp-form-section">
        @include('admin.roles._form')
        <button type="submit" class="erp-btn-primary w-full justify-center !py-2.5">Create Role</button>
    </div>
</form>
@endsection
