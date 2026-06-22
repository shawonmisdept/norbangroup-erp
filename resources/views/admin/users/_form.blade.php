<div class="flex items-start gap-4 pb-4 border-b border-erp-border">
    <div>
        @if(isset($user))
            @include('partials.user-avatar', ['user' => $user, 'size' => '180'])
        @else
            <div class="w-[180px] h-[180px] flex items-center justify-center rounded-sm border border-dashed border-erp-border bg-gray-50 text-xs text-gray-400">180 × 180</div>
        @endif
    </div>
    <div class="flex-1">
        <label class="erp-form-label">Photo (180 × 180 px)</label>
        <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"
               class="w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-sm file:border-0 file:text-xs file:font-medium file:bg-brand file:text-white">
        @error('photo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label class="erp-form-label">Name</label>
    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required class="erp-input !text-xs">
    @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
</div>

<div>
    <label class="erp-form-label">Email</label>
    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required class="erp-input !text-xs">
    @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
</div>

<div>
    <label class="erp-form-label">Password {{ isset($user) ? '(leave blank to keep)' : '' }}</label>
    <input type="password" name="password" {{ isset($user) ? '' : 'required' }} class="erp-input !text-xs">
    @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
</div>

<div>
    <label class="erp-form-label">Confirm Password</label>
    <input type="password" name="password_confirmation" class="erp-input !text-xs">
</div>

@if(isset($roles))
<div>
    <label class="erp-form-label">Role</label>
    <select name="role_id" required class="erp-input !text-xs">
        @foreach($roles as $id => $name)
            <option value="{{ $id }}" {{ (string) old('role_id', $user->role_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
    @error('role_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
</div>

@if(isset($factories))
<div>
    <label class="erp-form-label">Factory (optional)</label>
    <select name="factory_id" class="erp-input !text-xs">
        <option value="">— No factory —</option>
        @foreach($factories as $id => $name)
            <option value="{{ $id }}" {{ (string) old('factory_id', $user->factory_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
    @error('factory_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
</div>
@endif
@endif
