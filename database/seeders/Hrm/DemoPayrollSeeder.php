<?php

namespace Database\Seeders\Hrm;

use App\Jobs\Hrm\SendPeriodPayslipsJob;
use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Models\User;
use App\Services\Hrm\PayrollProcessor;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoPayrollSeeder extends Seeder
{
    /** @var list<string> */
    private array $employeeCodes = [
        'NCL-D001', 'NCL-D002', 'NCL-D003', 'NCL-D004', 'NCL-D005',
        'NCL-D006', 'NCL-D007', 'NCL-D008', 'NCL-D009', 'NCL-D010',
        'NCL-D011', 'NCL-D012', 'NCL-D013', 'NCL-D014', 'NCL-D015',
        'NCL-D016', 'NCL-D017', 'NCL-D018', 'NCL-D019', 'NCL-D020',
    ];

    public function run(): void
    {
        $factory = Factory::where('name', 'Norban Comtex Limited')->where('is_active', true)->first();

        if (! $factory) {
            $this->command?->warn('Norban Comtex Limited not found. Run db:seed first.');

            return;
        }

        $admin = User::query()->whereHas('role', fn ($q) => $q->where('name', 'Administrator'))->first()
            ?? User::query()->first();

        if (! $admin) {
            $this->command?->warn('No admin user found for payroll run.');

            return;
        }

        $year = 2026;
        $month = 6;

        $employees = Employee::query()
            ->where('factory_id', $factory->id)
            ->whereIn('employee_code', $this->employeeCodes)
            ->whereIn('status', ['active', 'probation'])
            ->with('salaryStructure')
            ->get()
            ->filter(fn (Employee $e) => $e->salaryStructure?->is_active);

        if ($employees->isEmpty()) {
            $this->command?->warn('No demo employees with salary structures. Run DemoEmployeeSeeder first.');

            return;
        }

        $this->ensurePortalAccess($employees);

        $attendancePeriod = AttendancePeriod::getOrCreateForMonth($factory->id, $year, $month);
        $payrollPeriod = PayrollPeriod::getOrCreateForMonth($factory->id, $year, $month);

        if ($payrollPeriod->isFrozen() || $payrollPeriod->status === 'calculated') {
            $this->resetPayrollPeriod($payrollPeriod, $attendancePeriod);
            $payrollPeriod->refresh();
            $attendancePeriod->refresh();
        }

        $this->seedAttendance($factory->id, $attendancePeriod, $employees, $year, $month);

        $attendancePeriod->update([
            'status'    => 'frozen',
            'frozen_at' => now(),
        ]);

        $processor = app(PayrollProcessor::class);
        $run = $processor->calculatePeriod($payrollPeriod->fresh(), $admin);
        $processor->freezePeriod($payrollPeriod->fresh(), $admin);

        try {
            SendPeriodPayslipsJob::dispatchSync($payrollPeriod->fresh()->id);
            $this->command?->info('  Payslip emails sent (where employee email is set).');
        } catch (\Throwable $e) {
            $this->command?->warn('  Payslip emails skipped: ' . $e->getMessage());
            $this->command?->warn('  Payslips are still available in Admin → Process and Employee Portal.');
        }

        $this->printSummary($payrollPeriod->fresh(), $run->employee_count);
    }

    private function resetPayrollPeriod(PayrollPeriod $payrollPeriod, AttendancePeriod $attendancePeriod): void
    {
        PayrollItem::query()->where('payroll_period_id', $payrollPeriod->id)->delete();
        $payrollPeriod->runs()->delete();

        $payrollPeriod->update([
            'status'          => 'draft',
            'calculated_at'   => null,
            'frozen_at'       => null,
            'payslips_sent_at'=> null,
            'calculated_by'   => null,
            'frozen_by'       => null,
        ]);

        $attendancePeriod->update([
            'status'    => 'draft',
            'frozen_at' => null,
        ]);
    }

    private function printSummary(PayrollPeriod $payrollPeriod, ?int $processed): void
    {
        $items = PayrollItem::query()
            ->with('employee')
            ->where('payroll_period_id', $payrollPeriod->id)
            ->where('net_pay', '>', 0)
            ->orderBy('employee_id')
            ->get();

        $this->command?->info("Demo payroll for {$payrollPeriod->periodLabel()} ({$payrollPeriod->statusLabel()}):");

        if ($processed !== null) {
            $this->command?->info("  Processed: {$processed} employee(s)");
        }

        if ($items->isEmpty()) {
            $this->command?->warn('  No payslip items with net pay > 0.');

            return;
        }

        $this->command?->table(
            ['Code', 'Name', 'Type', 'Gross', 'Deductions', 'Net Pay'],
            $items->map(fn ($i) => [
                $i->employee->employee_code,
                $i->employee->name,
                $i->pay_type,
                number_format((float) $i->gross_pay, 2),
                number_format($i->totalDeductions(), 2),
                number_format((float) $i->net_pay, 2),
            ])->all()
        );

        $this->command?->info('Admin: /admin/hrm/salary/process → June 2026');
        $this->command?->info('Employee portal: NCL-D001 / password → Payslips');
    }

    /** @param \Illuminate\Support\Collection<int, Employee> $employees */
    private function seedAttendance(int $factoryId, AttendancePeriod $period, $employees, int $year, int $month): void
    {
        $start = Carbon::create($year, $month, 1);
        $dates = CarbonPeriod::create($start, $start->copy()->endOfMonth());

        foreach ($employees as $index => $employee) {
            $isWages = $employee->salaryStructure?->pay_type === 'wages';
            $dayNum = 0;
            $schedule = app(\App\Services\Hrm\EmployeeScheduleService::class);

            foreach ($dates as $date) {
                if ($schedule->isWeekend($employee, $date)) {
                    continue;
                }

                $dayNum++;

                if ($dayNum > 22) {
                    break;
                }

                $status = match (true) {
                    $dayNum === 5 && $index % 3 === 0 => 'absent',
                    $dayNum === 8 && $index % 4 === 0 => 'late',
                    $dayNum === 12 && $index % 5 === 0 => 'half_day',
                    default => 'present',
                };

                $halfDayType = $status === 'half_day'
                    ? ($index % 2 === 0 ? 'first_half' : 'second_half')
                    : null;

                $workMinutes = match ($status) {
                    'present' => $isWages && $index % 2 === 0 ? 600 : 480,
                    'late'    => 450,
                    'half_day'=> 240,
                    default   => 0,
                };

                AttendanceDailyLog::updateOrCreate(
                    [
                        'employee_id'     => $employee->id,
                        'attendance_date' => $date->toDateString(),
                    ],
                    [
                        'factory_id'           => $factoryId,
                        'attendance_period_id' => $period->id,
                        'shift_id'             => $employee->shift_id,
                        'status'               => $status,
                        'work_minutes'         => $workMinutes,
                        'half_day_type'        => $halfDayType,
                        'half_day_pay_ratio'   => $status === 'half_day' ? 0.5 : null,
                        'punch_count'          => $status === 'absent' ? 0 : 2,
                    ]
                );
            }
        }
    }

    /** @param \Illuminate\Support\Collection<int, Employee> $employees */
    private function ensurePortalAccess($employees): void
    {
        foreach ($employees as $employee) {
            if (! $employee->email) {
                $employee->update([
                    'email' => strtolower($employee->employee_code) . '@demo.norbangroup.local',
                ]);
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
}
