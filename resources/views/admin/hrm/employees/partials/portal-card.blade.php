@if(auth()->user()->canManageEmployeeSubmodule('employees'))
    <div class="erp-panel">
        <div class="erp-panel-head">
            <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Employee Portal</h2>
        </div>
        <div class="erp-panel-body space-y-4 text-sm">
            @if(session('portal_password'))
                <div class="bg-amber-50 border border-amber-200 rounded-sm p-3 text-xs">
                    <p class="font-semibold text-amber-900 mb-1">Generated portal password (share once with employee)</p>
                    <code class="font-mono text-sm bg-white px-2 py-1 rounded border border-amber-200">{{ session('portal_password') }}</code>
                </div>
            @endif

            @if($employee->portalUser)
                <div class="flex flex-wrap items-center gap-2">
                    @if($employee->hasPortalAccess())
                        <span class="erp-badge bg-green-100 text-green-800">Portal active</span>
                    @elseif($employee->portalUser->is_active)
                        <span class="erp-badge bg-amber-100 text-amber-800">Portal disabled (employee status)</span>
                    @else
                        <span class="erp-badge bg-gray-100 text-gray-600">Portal disabled</span>
                    @endif
                    @if($employee->portalUser->last_login_at)
                        <span class="text-xs text-gray-500">Last login {{ $employee->portalUser->last_login_at->format('d M Y H:i') }}</span>
                    @else
                        <span class="text-xs text-gray-500">Never signed in</span>
                    @endif
                </div>

                <p class="text-xs text-gray-500">
                    Login URL: <a href="{{ route('employee.login') }}" class="text-brand hover:underline" target="_blank">{{ route('employee.login') }}</a>
                </p>

                @if($employee->portalUser->is_active && $employee->hasPortalAccess())
                    <form method="POST" action="{{ route('admin.hrm.employees.portal.update', $employee) }}" class="space-y-3 pt-2 border-t border-erp-border">
                        @csrf @method('PUT')
                        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Reset password</p>
                        <div>
                            <label class="erp-form-label">New password</label>
                            <input type="password" name="portal_password" required class="erp-input !text-xs">
                        </div>
                        <div>
                            <label class="erp-form-label">Confirm password</label>
                            <input type="password" name="portal_password_confirmation" required class="erp-input !text-xs">
                        </div>
                        <button type="submit" class="erp-btn-secondary !py-1.5 !px-3 text-xs">Reset Portal Password</button>
                    </form>

                    <form method="POST" action="{{ route('admin.hrm.employees.portal.destroy', $employee) }}" data-confirm="Disable portal access for this employee?">
                        @csrf @method('DELETE')
                        <button type="submit" class="erp-btn-danger !py-1.5 !px-3 text-xs">Disable Portal Access</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.hrm.employees.portal.store', $employee) }}" class="space-y-3">
                        @csrf
                        <p class="text-xs text-gray-500">Re-enable portal access for this employee.</p>
                        <div>
                            <label class="erp-form-label">Password (optional — auto-generate if blank)</label>
                            <input type="password" name="portal_password" class="erp-input !text-xs">
                        </div>
                        <div>
                            <label class="erp-form-label">Confirm password</label>
                            <input type="password" name="portal_password_confirmation" class="erp-input !text-xs">
                        </div>
                        <button type="submit" class="erp-btn-primary !py-1.5 !px-3 text-xs">Enable Portal Access</button>
                    </form>
                @endif
            @else
                <p class="text-xs text-gray-500">No portal account yet. Enable access so the employee can sign in at the employee portal.</p>
                <form method="POST" action="{{ route('admin.hrm.employees.portal.store', $employee) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="erp-form-label">Password (optional — auto-generate if blank)</label>
                        <input type="password" name="portal_password" class="erp-input !text-xs">
                    </div>
                    <div>
                        <label class="erp-form-label">Confirm password</label>
                        <input type="password" name="portal_password_confirmation" class="erp-input !text-xs">
                    </div>
                    <button type="submit" class="erp-btn-primary !py-1.5 !px-3 text-xs">Enable Portal Access</button>
                </form>
            @endif
        </div>
    </div>
@endif
