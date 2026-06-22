@extends('layouts.admin')

@section('title', 'My Profile')

@section('breadcrumbs')
    <span class="text-gray-800 font-medium">My Profile</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', ['title' => 'My Profile', 'subtitle' => 'Account settings and password'])

<form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="max-w-lg">
    @csrf @method('PUT')
    <div class="erp-form-section">
        <div class="flex items-start gap-4 pb-4 border-b border-erp-border">
            @include('partials.user-avatar', ['user' => $user, 'size' => '180'])
            <div class="flex-1">
                <p class="erp-form-label !mb-0.5">User ID</p>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded-sm font-mono">{{ $user->user_code }}</code>
                <div class="mt-3">
                    <label class="erp-form-label">Photo (180 × 180 px)</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"
                           class="w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-sm file:border-0 file:text-xs file:font-medium file:bg-brand file:text-white">
                    @error('photo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div>
            <label class="erp-form-label">Name</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="erp-input !text-xs">
            @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="erp-form-label">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="erp-input !text-xs">
            @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="erp-form-label">Role</label>
            <input type="text" value="{{ $user->roleLabel() }}" disabled class="erp-input !text-xs bg-gray-50 text-gray-500">
            <p class="text-[11px] text-gray-400 mt-1">Contact administrator to change role.</p>
        </div>

        <div class="pt-2 border-t border-erp-border">
            <p class="erp-form-label mb-3">Change Password</p>
            <div class="space-y-3">
                <div>
                    <label class="erp-form-label">New Password</label>
                    <input type="password" name="password" class="erp-input !text-xs" placeholder="Leave blank to keep current">
                    @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="erp-form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="erp-input !text-xs">
                </div>
            </div>
        </div>

        <button type="submit" class="erp-btn-primary w-full justify-center !py-2.5">Update Profile</button>
    </div>
</form>
@endsection
