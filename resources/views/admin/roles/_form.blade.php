<div>
    <label class="erp-form-label">Role Name</label>
    <input type="text" name="name" value="{{ old('name', $role->name ?? '') }}" required
           class="erp-input !text-xs" placeholder="e.g. Production Team">
    @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
</div>

@php
    $selected = old('permissions', $role->permissions ?? []);
@endphp

<div x-data='rolePermissions(@json(array_values($selected)))'>
    <label class="erp-form-label mb-2">Module Access Permissions</label>
    <div class="space-y-3 max-h-[32rem] overflow-y-auto border border-erp-border rounded-sm bg-gray-50 p-3">
        @foreach($permissionGroups as $groupName => $permissions)
            @php
                $groupSlug = str_replace([' ', '—', '&'], ['_', '', 'and'], $groupName);
                $isMasterGroup = ! in_array($groupName, ['Operations', 'Administration', 'Master Data — All Modules'], true);
            @endphp
            <section class="bg-white border border-erp-border rounded-sm overflow-hidden">
                <div class="flex items-center justify-between gap-2 px-3 py-2 bg-gray-50 border-b border-erp-border">
                    <h3 class="text-[11px] font-bold text-gray-600 uppercase tracking-wide">{{ $groupName }}</h3>
                    @if($isMasterGroup)
                        <div class="flex gap-1 shrink-0">
                            <button type="button"
                                    @click="setGroup(groupKeys('{{ $groupSlug }}').filter(k => k.endsWith('.view')), true)"
                                    class="text-[10px] font-semibold text-brand hover:text-brand-dark uppercase tracking-wide">
                                All View
                            </button>
                            <span class="text-gray-300">|</span>
                            <button type="button"
                                    @click="setGroup(groupKeys('{{ $groupSlug }}').filter(k => k.endsWith('.manage')), true)"
                                    class="text-[10px] font-semibold text-brand hover:text-brand-dark uppercase tracking-wide">
                                All Manage
                            </button>
                        </div>
                    @endif
                </div>
                <div class="p-2 space-y-0.5 {{ $isMasterGroup ? 'grid sm:grid-cols-2 gap-x-3' : '' }}">
                    @foreach($permissions as $key => $label)
                        <label class="flex items-start gap-2.5 text-xs text-gray-700 cursor-pointer py-1.5 px-2 hover:bg-gray-50 rounded-sm">
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $key }}"
                                   data-permission="{{ $key }}"
                                   data-group="{{ $groupSlug }}"
                                   class="rounded border-gray-300 text-brand focus:ring-brand mt-0.5 shrink-0"
                                   @change="toggle('{{ $key }}', $event.target.checked)"
                                   {{ in_array($key, $selected, true) ? 'checked' : '' }}>
                            <span class="flex-1 min-w-0">
                                <span class="block leading-snug">{{ $label }}</span>
                                <code class="text-[10px] text-gray-400">{{ $key }}</code>
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
    @error('permissions')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
</div>
