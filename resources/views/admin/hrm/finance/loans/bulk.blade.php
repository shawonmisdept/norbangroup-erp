@extends('layouts.admin')
@section('title', 'Bulk Festival Advance')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Bulk Festival Advance',
    'subtitle' => 'Disburse salary advance to many employees before Eid or other festivals — EMI auto-calculated',
    'actions' => '<a href="' . route('admin.hrm.finance.loans.index') . '" class="erp-btn-secondary">← Loans</a>',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.finance.loans.bulk') }}" class="erp-filter-bar">
            <div class="erp-filter-field">
                <label class="erp-form-label">Factory</label>
                <select name="factory_id" class="erp-input !text-xs" onchange="this.form.submit()" required>
                    <option value="">Select factory</option>
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" {{ $filterFactoryId === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @if($filterFactoryId)
            <div class="erp-filter-field-grow">
                <label class="erp-form-label">Department</label>
                <select name="department_id" class="erp-input !text-xs" onchange="this.form.submit()">
                    <option value="">All departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $filterDeptId === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </form>
    </div>
</div>

@if($canManage && $filterFactoryId && $employees->isNotEmpty())
<form method="POST" action="{{ route('admin.hrm.finance.loans.bulk.store') }}"
      data-confirm="Disburse festival advance to selected employees?"
      data-confirm-variant="warning"
      data-confirm-ok="Yes, disburse"
      x-data="bulkAdvanceForm({{ json_encode(old('amounts', [])) }}, {{ old('default_amount', 0) }}, {{ old('total_installments', 1) }})">
    @csrf
    <input type="hidden" name="factory_id" value="{{ $filterFactoryId }}">

    <div class="erp-panel mb-4">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase tracking-wide text-gray-600">Batch settings</h2></div>
        <div class="erp-panel-body grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="erp-form-label">Default advance (৳)</label>
                <input type="number" step="0.01" min="0" name="default_amount" x-model.number="defaultAmount"
                       class="erp-input" placeholder="Same for all selected">
            </div>
            <div>
                <label class="erp-form-label">Installments</label>
                <input type="number" name="total_installments" min="1" max="60" x-model.number="installments" class="erp-input" required>
            </div>
            <div class="sm:col-span-2">
                <label class="erp-form-label">Notes (e.g. Eid-ul-Fitr 2026)</label>
                <input type="text" name="notes" value="{{ old('notes') }}" class="erp-input" maxlength="1000" placeholder="Festival / reason for advance">
            </div>
            <div class="flex items-end gap-4 sm:col-span-2 lg:col-span-4">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="auto_approve" value="1" class="rounded border-gray-300" checked>
                    Auto-approve &amp; create EMI schedule
                </label>
                <button type="button" class="erp-btn-secondary !py-1.5 !px-3 text-xs" @click="fillDefaultAmounts()">
                    Fill selected with default amount
                </button>
            </div>
        </div>
    </div>

    <div class="erp-panel mb-4 overflow-hidden">
        <div class="erp-panel-head flex-wrap gap-2">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-600">Employees</h2>
            <button type="submit" class="erp-btn-primary !py-1.5 !px-3 text-xs"
                   >
                Disburse Advances
            </button>
        </div>
        <table class="erp-table">
            <thead>
                <tr>
                    <th class="w-10"><input type="checkbox" class="rounded border-gray-300" @change="toggleAll($event.target.checked)" checked></th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th class="text-right">Gross</th>
                    <th class="text-right">Advance (৳)</th>
                    <th class="text-right">EMI (৳)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $row)
                    @php $emp = $row['employee']; @endphp
                    <tr class="{{ $row['has_open_loan'] ? 'opacity-50' : '' }}">
                        <td>
                            @unless($row['has_open_loan'])
                                <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                                       class="emp-check rounded border-gray-300" checked>
                            @endunless
                        </td>
                        <td>
                            <span class="font-mono text-[10px] text-gray-400">{{ $emp->employee_code }}</span>
                            <span class="block text-sm font-medium">{{ $emp->name }}</span>
                        </td>
                        <td class="text-xs">{{ $emp->department?->name ?? '—' }}</td>
                        <td class="text-right tabular-nums text-xs">৳{{ number_format($row['gross'], 2) }}</td>
                        <td class="text-right">
                            @if($row['has_open_loan'])
                                <span class="text-xs text-gray-400">—</span>
                            @else
                                <input type="number" step="0.01" min="0" name="amounts[{{ $emp->id }}]"
                                       x-model.number="amounts['{{ $emp->id }}']" class="erp-input !w-28 !text-xs text-right ml-auto" placeholder="Amount">
                            @endif
                        </td>
                        <td class="text-right tabular-nums text-xs text-brand font-medium">
                            @unless($row['has_open_loan'])
                                <span x-text="emiFor(amounts['{{ $emp->id }}'])"></span>
                            @else
                                —
                            @endunless
                        </td>
                        <td class="text-xs {{ $row['has_open_loan'] ? 'text-amber-600' : 'text-green-600' }}">
                            {{ $row['has_open_loan'] ? 'Open loan' : 'Eligible' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</form>

<script>
function bulkAdvanceForm(initialAmounts, defaultAmount, installments) {
    return {
        amounts: initialAmounts || {},
        defaultAmount: defaultAmount || 0,
        installments: installments || 1,
        emiFor(amount) {
            const principal = parseFloat(amount) || 0;
            const n = parseInt(this.installments, 10) || 1;
            if (principal <= 0) return '—';
            return '৳' + (Math.round((principal / n) * 100) / 100).toFixed(2);
        },
        fillDefaultAmounts() {
            document.querySelectorAll('.emp-check:checked').forEach(cb => {
                this.amounts[cb.value] = this.defaultAmount;
            });
        },
        toggleAll(checked) {
            document.querySelectorAll('.emp-check').forEach(cb => { cb.checked = checked; });
        }
    };
}
</script>
@elseif($filterFactoryId && $employees->isEmpty())
    <div class="erp-panel"><div class="erp-panel-body text-center text-gray-400 py-8">No active employees in this factory.</div></div>
@elseif(!$filterFactoryId)
    <div class="erp-panel"><div class="erp-panel-body text-center text-gray-400 py-8">Select a factory to load employees.</div></div>
@endif
@endsection
