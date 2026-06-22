@extends('layouts.admin')

@section('title', 'Edit User')

@section('breadcrumbs')
    <a href="{{ route('admin.users.index') }}" class="hover:text-brand">Users</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $user->user_code }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Edit User', 'subtitle' => $user->name])

<form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data" class="max-w-lg">
    @csrf @method('PUT')
    <div class="erp-form-section">
        @include('admin.users._form', ['roles' => $roles, 'factories' => $factories])
        <button type="submit" class="erp-btn-primary w-full justify-center !py-2.5">Update User</button>
    </div>
</form>
@endsection
