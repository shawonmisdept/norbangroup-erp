@extends('layouts.admin')

@section('title', 'Attendance Policy')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Policy</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'policy'])

@include('partials.erp.page-header', [
    'title' => 'Attendance Policy',
    'subtitle' => 'Late grace minutes, consecutive late rule (3 free → 4th day salary cut), deduction basis',
])

<div class="erp-panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Factory</th>
                    <th>Late Grace</th>
                    <th>Consecutive Grace</th>
                    <th>Deduction Basis</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($policies as $policy)
                    <tr>
                        <td class="font-medium text-sm">{{ $policy->factory?->name }}</td>
                        <td class="text-sm tabular-nums">{{ $policy->late_grace_minutes }} min</td>
                        <td class="text-sm tabular-nums">{{ $policy->consecutive_late_grace_days }} days</td>
                        <td class="text-xs">{{ $policy->late_deduction_basis === 'gross' ? 'Gross / 26' : 'Basic / 26' }}</td>
                        <td>
                            @if($policy->is_active)
                                <span class="erp-badge bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="erp-badge bg-gray-100 text-gray-600">Inactive</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if(auth()->user()->canManageAttendanceSubmodule('policy'))
                                @include('partials.erp.table-actions', [
                                    'editUrl' => route('admin.hrm.attendance.policy.edit', $policy),
                                ])
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-10 text-gray-400">No policies yet. Policies are created automatically when a factory is first processed.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($policies->hasPages())
        <div class="px-4 py-3 border-t border-erp-border">{{ $policies->links() }}</div>
    @endif
</div>

<div class="erp-panel mt-4">
    <div class="erp-panel-body text-xs text-gray-600 space-y-2">
        <p><strong>Late rule:</strong> First {{ $policies->first()?->consecutive_late_grace_days ?? 3 }} consecutive late days — no salary cut. On the next consecutive late day — one day's salary is deducted, then the streak resets.</p>
        <p><strong>Late acceptance:</strong> Employees with standing privilege or approved applications are exempt from late salary deduction.</p>
    </div>
</div>
@endsection
