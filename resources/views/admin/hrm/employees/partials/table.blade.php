<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Employees</h2>
        <span class="text-[11px] text-gray-400">{{ $employees->total() }} record(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th class="w-12 text-center">SN</th>
                    <th class="w-14">Photo</th>
                    <th>Employee ID</th>
                    <th>Employee Name</th>
                    <th class="hidden md:table-cell">Department</th>
                    <th class="hidden lg:table-cell">Designation</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td class="text-center text-xs text-gray-400 tabular-nums">
                            {{ ($employees->firstItem() ?? 0) + $loop->index }}
                        </td>
                        <td>
                            @if($employee->photoUrl())
                                <img src="{{ $employee->photoUrl() }}" alt="{{ $employee->name }}" class="erp-employee-index-photo">
                            @else
                                <div class="erp-employee-index-photo-fallback">{{ $employee->initials() }}</div>
                            @endif
                        </td>
                        <td>
                            <code class="text-[11px] bg-gray-100 px-1.5 py-0.5 rounded-sm font-mono">{{ $employee->employee_code }}</code>
                        </td>
                        <td class="font-medium text-gray-900">{{ $employee->name }}</td>
                        <td class="hidden md:table-cell text-xs text-gray-600">{{ $employee->department?->name ?? '—' }}</td>
                        <td class="hidden lg:table-cell text-xs text-gray-600">{{ $employee->designation?->name ?? '—' }}</td>
                        <td>
                            @php
                                $badge = match($employee->status) {
                                    'active' => 'bg-green-100 text-green-800',
                                    'probation' => 'bg-amber-100 text-amber-800',
                                    'suspended' => 'bg-orange-100 text-orange-800',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="erp-badge {{ $badge }}">{{ $employee->statusLabel() }}</span>
                        </td>
                        <td class="text-right">
                            <div class="erp-table-actions">
                                <a href="{{ route('admin.hrm.employees.show', $employee) }}" class="erp-btn-sm-secondary">View</a>
                                @if(auth()->user()->canManageEmployeeSubmodule('employees'))
                                    <a href="{{ route('admin.hrm.employees.edit', $employee) }}" class="erp-btn-sm-primary">Edit</a>
                                    <form method="POST" action="{{ route('admin.hrm.employees.destroy', $employee) }}" class="inline"
                                          data-confirm="Remove employee &quot;{{ $employee->name }}&quot; ({{ $employee->employee_code }})?"
                                          data-confirm-title="Remove employee"
                                          data-confirm-ok="Yes, remove">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="erp-btn-danger !py-1 !px-2">Del</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-10 text-gray-400">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
        <div class="px-4 py-3 border-t border-erp-border bg-gray-50/50">{{ $employees->links() }}</div>
    @endif
</div>
