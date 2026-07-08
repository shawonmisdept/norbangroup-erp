@extends('layouts.admin')
@section('title', 'Salary Banks')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Salary Banks',
    'subtitle' => 'Banks used for employee salary transfer (Shahjalal, BRAC, etc.)',
    'actions' => auth()->user()->canManageSalarySubmodule('banks')
        ? '<a href="' . route('admin.hrm.salary.banks.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">+ Add Bank</a>'
        : '',
])
@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'banks'])

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Bank</th>
                <th>Factory</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($banks as $bank)
                <tr>
                    <td><code class="text-xs">{{ $bank->code }}</code></td>
                    <td>
                        <p class="font-medium text-sm">{{ $bank->name }}</p>
                        @if($bank->short_name && $bank->short_name !== $bank->name)
                            <p class="text-[11px] text-gray-400">{{ $bank->short_name }}</p>
                        @endif
                    </td>
                    <td class="text-xs">{{ $bank->factory?->name ?? '—' }}</td>
                    <td><span class="text-xs {{ $bank->is_active ? 'text-green-600' : 'text-gray-400' }}">{{ $bank->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-right">
                        @include('admin.hrm.salary.partials.row-actions', [
                            'editRoute' => auth()->user()->canManageSalarySubmodule('banks') ? route('admin.hrm.salary.banks.edit', $bank) : null,
                            'destroyRoute' => auth()->user()->canManageSalarySubmodule('banks') ? route('admin.hrm.salary.banks.destroy', $bank) : null,
                            'canManage' => auth()->user()->canManageSalarySubmodule('banks'),
                            'confirm' => 'Delete bank "' . $bank->name . '"?',
                        ])
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-8 text-gray-400">No salary banks yet. Add Shahjalal, BRAC, etc.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($banks->hasPages())<div class="px-4 py-3 border-t border-erp-border">{{ $banks->links() }}</div>@endif
</div>
@endsection
