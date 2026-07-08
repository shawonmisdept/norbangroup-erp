<?php

namespace Database\Seeders\Hrm;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\SalaryBank;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryStructure;
use App\Models\User;
use App\Services\Hrm\DisbursementSplitService;
use App\Services\Hrm\PayrollProcessor;
use App\Services\Hrm\SalaryFormulaCalculator;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Employee1053DemoSeeder extends Seeder
{
    private const EMPLOYEE_CODE = '1053';

    private const GROSS_SALARY = 35000.0;

    private const BANK_ACCOUNT = '234523452345';

    private const BANK_DISBURSEMENT_AMOUNT = 25000.0;

    public function run(): void
    {
        $factory = Factory::where('name', 'Head Office')->where('is_active', true)->first();

        if (! $factory) {
            $this->command?->warn('Head Office factory not found. Run db:seed first.');

            return;
        }

        $employee = Employee::query()
            ->where('factory_id', $factory->id)
            ->where('employee_code', self::EMPLOYEE_CODE)
            ->first();

        if (! $employee) {
            $this->command?->warn('Employee ' . self::EMPLOYEE_CODE . ' not found. Run HeadOfficeEmployeeSeeder first.');

            return;
        }

        $admin = User::query()->whereHas('role', fn ($q) => $q->where('name', 'Administrator'))->first()
            ?? User::query()->first();

        if (! $admin) {
            $this->command?->warn('No admin user found for payroll run.');

            return;
        }

        $this->call(SalaryBankSeeder::class);

        $grade = SalaryGrade::query()
            ->where('factory_id', $factory->id)
            ->where('code', 'SR-01')
            ->where('is_active', true)
            ->first();

        if (! $grade) {
            $this->command?->warn('SR-01 grade not found. Run SalaryLegacySeeder first.');

            return;
        }

        $salaryBank = SalaryBank::query()
            ->where('factory_id', $factory->id)
            ->where('code', 'SJIB')
            ->where('is_active', true)
            ->first();

        $calculator = app(SalaryFormulaCalculator::class);
        $amounts = $calculator->calculate($grade, self::GROSS_SALARY);

        $structure = SalaryStructure::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'factory_id'               => $factory->id,
                'salary_grade_id'          => $grade->id,
                'gross_salary'             => self::GROSS_SALARY,
                'pay_type'                 => 'salary',
                'payment_method'           => 'split',
                'salary_bank_id'           => $salaryBank?->id,
                'bank_account'             => self::BANK_ACCOUNT,
                'bank_disbursement_amount' => self::BANK_DISBURSEMENT_AMOUNT,
                'effective_from'           => $employee->joining_date,
                'is_active'                => true,
            ]
        );

        $structure->syncLegacyFromHeads($amounts);
        $structure->save();

        $this->ensurePortalAccess($employee);

        $year = 2026;
        $month = 6;

        $attendancePeriod = AttendancePeriod::getOrCreateForMonth($factory->id, $year, $month);
        $payrollPeriod = PayrollPeriod::getOrCreateForMonth($factory->id, $year, $month);

        if ($payrollPeriod->isFrozen() || $payrollPeriod->status === 'calculated') {
            $this->resetPayrollPeriod($payrollPeriod, $attendancePeriod);
            $payrollPeriod->refresh();
            $attendancePeriod->refresh();
        }

        $this->seedAttendance($factory->id, $attendancePeriod, $employee, $year, $month);

        $attendancePeriod->update([
            'status'    => 'frozen',
            'frozen_at' => now(),
        ]);

        $processor = app(PayrollProcessor::class);
        $processor->calculatePeriod($payrollPeriod->fresh(), $admin);

        $item = PayrollItem::query()
            ->where('payroll_period_id', $payrollPeriod->id)
            ->where('employee_id', $employee->id)
            ->first();

        if ($item && (float) $item->cash_pay_amount > 0) {
            app(DisbursementSplitService::class)->markCashDisbursed($item->fresh(), $admin);
        }

        $processor->freezePeriod($payrollPeriod->fresh(), $admin);
        $payrollPeriod->refresh();

        $item = PayrollItem::query()
            ->with(['employee', 'salaryBank'])
            ->where('payroll_period_id', $payrollPeriod->id)
            ->where('employee_id', $employee->id)
            ->first();

        $this->command?->info("Demo data seeded for {$employee->employee_code} — {$employee->name} ({$payrollPeriod->periodLabel()}, {$payrollPeriod->statusLabel()}):");

        if (! $item) {
            $this->command?->warn('  No payroll item created.');

            return;
        }

        $this->command?->table(
            ['Field', 'Value'],
            [
                ['Gross Pay', number_format((float) $item->gross_pay, 2)],
                ['Deductions', number_format($item->totalDeductions(), 2)],
                ['Net Pay', number_format((float) $item->net_pay, 2)],
                ['Bank Pay', number_format((float) $item->bank_pay_amount, 2)],
                ['Cash Pay', number_format((float) $item->cash_pay_amount, 2)],
                ['Salary Bank', $item->salaryBank?->short_name ?? '—'],
                ['Bank Account', $structure->bank_account],
                ['Portal Login', ($employee->email ?? '—') . ' / password'],
            ]
        );

        $this->command?->info('Admin: /admin/hrm/salary/process → June 2026');
        $this->command?->info('Bank Ledger: /admin/hrm/salary/bank-ledger');
        $this->command?->info('Employee portal: Payslips (frozen period only)');
    }

    private function resetPayrollPeriod(PayrollPeriod $payrollPeriod, AttendancePeriod $attendancePeriod): void
    {
        PayrollItem::query()->where('payroll_period_id', $payrollPeriod->id)->delete();
        $payrollPeriod->runs()->delete();

        $payrollPeriod->update([
            'status'           => 'draft',
            'calculated_at'    => null,
            'frozen_at'        => null,
            'payslips_sent_at' => null,
            'calculated_by'    => null,
            'frozen_by'        => null,
        ]);

        $attendancePeriod->update([
            'status'    => 'draft',
            'frozen_at' => null,
        ]);
    }

    private function seedAttendance(int $factoryId, AttendancePeriod $period, Employee $employee, int $year, int $month): void
    {
        $start = Carbon::create($year, $month, 1);
        $dates = CarbonPeriod::create($start, $start->copy()->endOfMonth());
        $schedule = app(\App\Services\Hrm\EmployeeScheduleService::class);
        $dayNum = 0;

        foreach ($dates as $date) {
            if ($schedule->isWeekend($employee, $date)) {
                continue;
            }

            $dayNum++;

            if ($dayNum > 22) {
                break;
            }

            AttendanceDailyLog::updateOrCreate(
                [
                    'employee_id'     => $employee->id,
                    'attendance_date' => $date->toDateString(),
                ],
                [
                    'factory_id'           => $factoryId,
                    'attendance_period_id' => $period->id,
                    'shift_id'             => $employee->shift_id,
                    'status'               => 'present',
                    'work_minutes'         => 480,
                    'half_day_type'        => null,
                    'half_day_pay_ratio'   => null,
                    'punch_count'          => 2,
                ]
            );
        }
    }

    private function ensurePortalAccess(Employee $employee): void
    {
        if (! $employee->email) {
            $employee->update(['email' => 'shawon.mis@norbangroup.com']);
        }

        EmployeePortalUser::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );
    }
}
