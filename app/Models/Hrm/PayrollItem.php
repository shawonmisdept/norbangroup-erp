<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $table = 'hrm_payroll_items';

    protected $fillable = [
        'factory_id', 'employee_id', 'payroll_period_id', 'payroll_run_id', 'pay_type',
        'present_days', 'absent_days', 'leave_days', 'late_days', 'late_forgiven_days', 'late_charge_days',
        'half_days', 'half_day_first', 'half_day_second', 'half_day_paid_units',
        'ot_hours', 'ot_amount',
        'basic_amount', 'allowances', 'gross_pay',
        'absent_deduction', 'late_deduction', 'other_deduction',
        'tds_amount', 'pf_employee_amount', 'pf_employer_amount', 'loan_deduction',
        'net_pay',
        'head_breakdown', 'payslip_sent_at',
        'payment_method', 'bank_account', 'notes',
    ];

    protected $casts = [
        'basic_amount'      => 'decimal:2',
        'allowances'        => 'decimal:2',
        'gross_pay'         => 'decimal:2',
        'ot_hours'          => 'decimal:2',
        'ot_amount'         => 'decimal:2',
        'absent_deduction'  => 'decimal:2',
        'late_deduction'    => 'decimal:2',
        'other_deduction'   => 'decimal:2',
        'tds_amount'        => 'decimal:2',
        'pf_employee_amount'=> 'decimal:2',
        'pf_employer_amount'=> 'decimal:2',
        'loan_deduction'    => 'decimal:2',
        'net_pay'           => 'decimal:2',
        'half_day_paid_units' => 'decimal:2',
        'head_breakdown'    => 'array',
        'payslip_sent_at'   => 'datetime',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function totalDeductions(): float
    {
        return (float) $this->absent_deduction + (float) $this->late_deduction + (float) $this->other_deduction;
    }

    public function paidDays(): int
    {
        return $this->present_days + $this->leave_days + (float) $this->half_day_paid_units;
    }

    /** @return array<string, string> */
    public function headLabels(): array
    {
        $codes = collect($this->head_breakdown['earnings'] ?? [])
            ->keys()
            ->merge(collect($this->head_breakdown['deductions'] ?? [])->keys())
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return [];
        }

        return SalaryHead::query()
            ->where('factory_id', $this->factory_id)
            ->whereIn('code', $codes->all())
            ->pluck('name', 'code')
            ->mapWithKeys(fn ($name, $code) => [strtoupper(trim((string) $code)) => $name])
            ->all();
    }

    public function headLabel(string $code): string
    {
        $code = strtoupper(trim($code));

        return match ($code) {
            'BASIC'  => 'Basic Salary',
            'GROSS'  => 'Gross Salary',
            'OT'     => 'Overtime',
            'ABSENT' => 'Absent Deduction',
            'LATE'   => 'Late Deduction',
            'TDS'    => 'Income Tax (TDS)',
            'PF'     => 'Provident Fund',
            'LOAN'   => 'Loan Recovery',
            'CANTEEN'=> 'Canteen Deduction',
            'FEST_BONUS' => 'Festival Bonus',
            'PERF_BONUS' => 'Performance Bonus',
            'PROD_INCENTIVE' => 'Production Incentive',
            'ATT_BONUS' => 'Attendance Bonus',
            default  => $this->headLabels()[$code] ?? $code,
        };
    }
}
