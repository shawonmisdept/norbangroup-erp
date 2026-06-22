@extends('layouts.admin')

@section('title', 'Add User')

@section('breadcrumbs')
    <a href="{{ route('admin.users.index') }}" class="hover:text-brand">Users</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">New</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', ['title' => 'New User', 'subtitle' => 'Create a team member account'])

<form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data" class="max-w-lg">
    @csrf
    <div class="erp-form-section">
        @include('admin.users._form', ['roles' => $roles, 'factories' => $factories])
        <button type="submit" class="erp-btn-primary w-full justify-center !py-2.5">Save User</button>
    </div>
</form>
@endsection
