@extends('layouts.admin')
@section('title', 'Increment Rules')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Increment Rules',
    'subtitle' => 'Auto increment by grade, tenure, percentage or fixed amount',
    'actions' => auth()->user()->canManageSalarySubmodule('increment-rules')
        ? '<a href="' . route('admin.hrm.salary.increment-rules.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">+ Add Rule</a>'
        : '',
])
@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'increment-rules'])

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Factory</th>
                <th>Grade</th>
                <th>Type</th>
                <th>Value</th>
                <th>Min Tenure</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rules as $rule)
                <tr>
                    <td class="font-medium text-sm">{{ $rule->name }}</td>
                    <td class="text-xs">{{ $rule->factory?->name }}</td>
                    <td class="text-xs">{{ $rule->salaryGrade?->name ?? 'All grades' }}</td>
                    <td class="text-xs">{{ $rule->typeLabel() }}</td>
                    <td class="text-xs tabular-nums">{{ $rule->valueLabel() }}</td>
                    <td class="text-xs">{{ $rule->min_tenure_months }} mo</td>
                    <td class="text-xs {{ $rule->is_active ? 'text-green-600' : 'text-gray-400' }}">{{ $rule->is_active ? 'Active' : 'Inactive' }}</td>
                    <td class="text-right">
                        @if(auth()->user()->canManageSalarySubmodule('increment-rules'))
                            <div class="erp-table-actions">
                                <a href="{{ route('admin.hrm.salary.increment-rules.edit', $rule) }}" class="erp-btn-sm-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.hrm.salary.increment-rules.destroy', $rule) }}" class="inline" onsubmit="return confirm(@js('Delete rule "' . $rule->name . '"?'))">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="erp-btn-danger !py-1 !px-2 text-[11px]">Del</button>
                                </form>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center py-8 text-gray-400">No increment rules yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($rules->hasPages())
        <div class="px-4 py-3 border-t">{{ $rules->links() }}</div>
    @endif
</div>
@endsection
