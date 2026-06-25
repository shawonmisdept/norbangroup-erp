@extends('layouts.admin')
@section('title', 'Gratuity Settlements')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Gratuity on Separation',
    'actions' => ($factoryId
        ? '<a href="' . route('admin.hrm.compliance.gratuity.export') . '?factory_id=' . $factoryId . '" class="erp-btn-secondary !text-xs">Export CSV</a>'
        : '')
        . '<a href="' . route('admin.hrm.compliance.hub') . '" class="erp-btn-secondary ml-2">← Hub</a>',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            @if(count($factories) > 1)
            <div>
                <label class="erp-form-label">Factory</label>
                <select name="factory_id" class="erp-input !text-xs">
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" {{ (int) ($filters['factory_id'] ?? $factoryId) === (int) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @else
                <input type="hidden" name="factory_id" value="{{ $factoryId }}">
            @endif
            <div>
                <label class="erp-form-label">Status</label>
                <select name="status" class="erp-input !text-xs">
                    <option value="">All</option>
                    <option value="calculated" {{ ($filters['status'] ?? '') === 'calculated' ? 'selected' : '' }}>Calculated</option>
                    <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <button type="submit" class="erp-btn-primary">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel">
    <div class="overflow-x-auto">
        <table class="erp-table w-full text-xs">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Separation</th>
                    <th>Years</th>
                    <th>Last Basic</th>
                    <th>Gratuity</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($settlements as $s)
                <tr>
                    <td>{{ $s->employee?->employee_code }} — {{ $s->employee?->name }}</td>
                    <td>{{ $s->separation_date->format('d M Y') }}</td>
                    <td>{{ $s->years_of_service }}</td>
                    <td>৳{{ number_format($s->last_basic_salary, 2) }}</td>
                    <td>৳{{ number_format($s->gratuity_amount, 2) }}</td>
                    <td>{{ ucfirst($s->status) }}</td>
                    <td class="text-right">
                        @include('partials.erp.table-actions', [
                            'viewUrl' => route('admin.hrm.compliance.gratuity.show', $s),
                        ])
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-8 text-gray-400">No gratuity settlements. Auto-created when employee status changes to resigned/terminated.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $settlements->links() }}</div>
</div>
@endsection
