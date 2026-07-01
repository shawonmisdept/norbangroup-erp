@extends('layouts.admin')

@section('title', 'Edit Role')

@section('breadcrumbs')
    <a href="{{ route('admin.roles.index') }}" class="hover:text-brand">Roles</a>
    <span>/</span>
    <a href="{{ route('admin.roles.show', $role) }}" class="hover:text-brand">{{ $role->name }}</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Edit</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Edit Role', 'subtitle' => $role->name])

<form method="POST" action="{{ route('admin.roles.update', $role) }}" class="max-w-4xl">
    @csrf @method('PUT')
    <div class="erp-form-section">
        @include('admin.roles._form')
        <button type="submit" class="erp-btn-primary w-full justify-center !py-2.5">Update Role</button>
    </div>
</form>
@endsection
