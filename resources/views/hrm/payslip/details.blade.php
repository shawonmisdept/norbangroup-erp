@php
    $period = $payslip->period;
    $structure = $employee->salaryStructure ?? null;
    $earnings = $payslip->head_breakdown['earnings'] ?? [];
    $deductions = $payslip->head_breakdown['deductions'] ?? [];
    $skipEarningCodes = ['BASIC', 'GROSS', 'OT'];
    $skipDeductionCodes = ['ABSENT', 'LATE'];
@endphp

@php
    $halfSummary = $payslip->head_breakdown['half_summary'] ?? null;
@endphp

<div class="payslip-document space-y-4">
    <div class="erp-panel">
        <div class="erp-panel-body">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Payslip</p>
                    <h2 class="text-lg font-bold text-gray-900">{{ $period->periodLabel() }}</h2>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $period->factory?->name ?? config('portal.name') }}
                        · {{ $period->start_date->format('d M') }} – {{ $period->end_date->format('d M Y') }}
                    </p>
                </div>
                <div class="text-left sm:text-right text-xs text-gray-600 space-y-1">
                    <p><span class="text-gray-400 uppercase tracking-wide">Pay Type</span> · {{ ucfirst($payslip->pay_type) }}</p>
                    @if($structure?->salaryGrade)
                        <p><span class="text-gray-400 uppercase tracking-wide">Grade</span> · {{ $structure->salaryGrade->code }} — {{ $structure->salaryGrade->name }}</p>
                    @endif
                    @if((float) $structure?->gross_salary > 0)
                        <p><span class="text-gray-400 uppercase tracking-wide">Monthly Gross</span> · ৳{{ number_format((float) $structure->gross_salary, 2) }}</p>
                    @elseif($payslip->pay_type === 'wages' && (float) $structure?->daily_wage > 0)
                        <p><span class="text-gray-400 uppercase tracking-wide">Daily Wage</span> · ৳{{ number_format((float) $structure->daily_wage, 2) }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="erp-panel">
        <div class="erp-panel-head">
            <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Employee Details</h3>
        </div>
        <div class="erp-panel-body grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3 text-sm">
            <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-wide">Name</p>
                <p class="font-medium">{{ $employee->name }}</p>
            </div>
            <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-wide">Employee Code</p>
                <p class="font-mono text-sm">{{ $employee->employee_code }}</p>
            </div>
            <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-wide">Designation</p>
                <p>{{ $employee->designation?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-wide">Department</p>
                <p>{{ $employee->department?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-wide">Joining Date</p>
                <p>{{ $employee->joining_date?->format('d M Y') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-wide">Payment</p>
                <p class="capitalize">{{ $payslip->payment_method ?? '—' }}</p>
                @if($payslip->bank_account)
                    <p class="text-xs text-gray-500 mt-0.5">{{ $payslip->bank_account }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="erp-panel">
        <div class="erp-panel-head">
            <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Attendance Summary</h3>
        </div>
        <div class="erp-panel-body payslip-attendance-grid">
            @foreach([
                ['label' => 'Present', 'value' => $payslip->present_days],
                ['label' => 'Absent', 'value' => $payslip->absent_days],
                ['label' => 'Leave', 'value' => $payslip->leave_days],
                ['label' => 'Late', 'value' => $payslip->late_days],
                ['label' => 'Half', 'value' => $payslip->half_days],
                ['label' => 'Paid', 'value' => $payslip->paidDays()],
                ['label' => 'OT Hrs', 'value' => number_format((float) $payslip->ot_hours, 1)],
            ] as $stat)
                <div class="payslip-attendance-stat">
                    <p class="stat-value">{{ $stat['value'] }}</p>
                    <p class="stat-label">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>
        @if(($payslip->half_days ?? 0) > 0)
            <div class="px-4 pb-4 pt-0">
                <div class="rounded-sm border border-orange-200/70 bg-orange-50/40 px-3 py-2 text-xs text-gray-600 flex flex-wrap gap-x-4 gap-y-1">
                    <span>First half: <strong class="tabular-nums">{{ $payslip->half_day_first ?? ($halfSummary['first_half'] ?? 0) }}</strong></span>
                    <span>Second half: <strong class="tabular-nums">{{ $payslip->half_day_second ?? ($halfSummary['second_half'] ?? 0) }}</strong></span>
                    @if(($halfSummary['auto'] ?? 0) > 0)
                        <span>Auto: <strong class="tabular-nums">{{ $halfSummary['auto'] }}</strong></span>
                    @endif
                    <span>Paid units: <strong class="tabular-nums">{{ number_format((float) ($payslip->half_day_paid_units ?? ($halfSummary['paid_units'] ?? 0)), 2) }}</strong></span>
                </div>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="erp-panel overflow-hidden">
            <div class="erp-panel-head">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Earnings</h3>
            </div>
            <div class="divide-y divide-erp-border text-sm">
                <div class="px-4 py-2.5 flex justify-between gap-3">
                    <span class="text-gray-600">{{ $payslip->pay_type === 'wages' ? 'Wages (paid days)' : $payslip->headLabel('BASIC') }}</span>
                    <span class="tabular-nums shrink-0">৳{{ number_format((float) $payslip->basic_amount, 2) }}</span>
                </div>
                @if((float) $payslip->allowances > 0)
                <div class="px-4 py-2.5 flex justify-between gap-3">
                    <span class="text-gray-600">Allowances</span>
                    <span class="tabular-nums shrink-0">৳{{ number_format((float) $payslip->allowances, 2) }}</span>
                </div>
                @endif
                @if((float) $payslip->ot_amount > 0)
                <div class="px-4 py-2.5 flex justify-between gap-3">
                    <span class="text-gray-600">{{ $payslip->headLabel('OT') }} ({{ number_format((float) $payslip->ot_hours, 1) }} hrs)</span>
                    <span class="tabular-nums shrink-0">৳{{ number_format((float) $payslip->ot_amount, 2) }}</span>
                </div>
                @endif
                @foreach($earnings as $code => $amount)
                    @if(! in_array(strtoupper($code), $skipEarningCodes, true) && (float) $amount > 0)
                    <div class="px-4 py-2.5 flex justify-between gap-3 text-xs sm:text-sm">
                        <span class="text-gray-500">{{ $payslip->headLabel($code) }}</span>
                        <span class="tabular-nums shrink-0">৳{{ number_format($amount, 2) }}</span>
                    </div>
                    @endif
                @endforeach
                <div class="px-4 py-2.5 flex justify-between gap-3 font-semibold bg-gray-50/50">
                    <span>Gross Pay</span>
                    <span class="tabular-nums shrink-0">৳{{ number_format((float) $payslip->gross_pay, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="erp-panel overflow-hidden">
            <div class="erp-panel-head">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Deductions</h3>
            </div>
            <div class="divide-y divide-erp-border text-sm">
                @if((float) $payslip->absent_deduction > 0)
                <div class="px-4 py-2.5 flex justify-between gap-3">
                    <span class="text-gray-600">{{ $payslip->headLabel('ABSENT') }}</span>
                    <span class="tabular-nums text-red-600 shrink-0">৳{{ number_format((float) $payslip->absent_deduction, 2) }}</span>
                </div>
                @endif
                @if((float) $payslip->late_deduction > 0)
                <div class="px-4 py-2.5 flex justify-between gap-3">
                    <span class="text-gray-600">
                        {{ $payslip->headLabel('LATE') }}
                        @if(($payslip->late_charge_days ?? 0) > 0)
                            ({{ $payslip->late_charge_days }} day{{ $payslip->late_charge_days > 1 ? 's' : '' }} × ৳{{ number_format((float) ($payslip->head_breakdown['late_summary']['day_rate'] ?? 0), 2) }})
                        @endif
                    </span>
                    <span class="tabular-nums text-red-600 shrink-0">৳{{ number_format((float) $payslip->late_deduction, 2) }}</span>
                </div>
                @elseif(($payslip->late_forgiven_days ?? 0) > 0)
                <div class="px-4 py-2.5 flex justify-between gap-3 text-xs">
                    <span class="text-gray-500">Late days forgiven (accepted)</span>
                    <span class="tabular-nums text-green-700">{{ $payslip->late_forgiven_days }} day(s)</span>
                </div>
                @endif
                @foreach($deductions as $code => $amount)
                    @if(! in_array(strtoupper($code), $skipDeductionCodes, true) && (float) $amount > 0)
                    <div class="px-4 py-2.5 flex justify-between gap-3">
                        <span class="text-gray-600">{{ $payslip->headLabel($code) }}</span>
                        <span class="tabular-nums text-red-600 shrink-0">৳{{ number_format($amount, 2) }}</span>
                    </div>
                    @endif
                @endforeach
                @if((float) $payslip->other_deduction > 0 && empty($deductions))
                <div class="px-4 py-2.5 flex justify-between gap-3">
                    <span class="text-gray-600">Other Deductions</span>
                    <span class="tabular-nums text-red-600 shrink-0">৳{{ number_format((float) $payslip->other_deduction, 2) }}</span>
                </div>
                @endif
                <div class="px-4 py-2.5 flex justify-between gap-3 font-semibold bg-gray-50/50">
                    <span>Total Deductions</span>
                    <span class="tabular-nums text-red-600 shrink-0">৳{{ number_format($payslip->totalDeductions(), 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="erp-panel bg-brand/5 border-brand/20">
        <div class="erp-panel-body flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Net Pay</p>
                <p class="text-3xl font-bold text-brand tabular-nums">৳{{ number_format((float) $payslip->net_pay, 2) }}</p>
            </div>
            <div class="text-xs text-gray-500 sm:text-right space-y-1">
                @if($payslip->payslip_sent_at ?? null)
                    <p>Emailed {{ $payslip->payslip_sent_at->format('d M Y H:i') }}</p>
                @endif
                @if($payslip->notes)
                    <p class="text-gray-600">{{ $payslip->notes }}</p>
                @endif
                <p class="text-[11px] text-gray-400">Computer-generated payslip · {{ now()->format('d M Y') }}</p>
            </div>
        </div>
    </div>
</div>
