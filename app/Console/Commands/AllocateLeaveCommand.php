<?php

namespace App\Console\Commands;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Services\Hrm\LeaveService;
use Illuminate\Console\Command;

class AllocateLeaveCommand extends Command
{
    protected $signature = 'hrm:allocate-leave
                            {--factory= : Factory ID}
                            {--year= : Allocation year (defaults to current year)}';

    protected $description = 'Ensure leave balances exist for active employees (yearly allocation)';

    public function handle(LeaveService $leaveService): int
    {
        $year = (int) ($this->option('year') ?: now()->year);

        $factoryQuery = Factory::query()->where('is_active', true);

        if ($this->option('factory')) {
            $factoryQuery->where('id', $this->option('factory'));
        }

        $factories = $factoryQuery->get();

        if ($factories->isEmpty()) {
            $this->error('No active factories found.');

            return self::FAILURE;
        }

        $total = 0;

        foreach ($factories as $factory) {
            $employees = Employee::query()
                ->where('factory_id', $factory->id)
                ->whereIn('status', ['active', 'probation'])
                ->get();

            foreach ($employees as $employee) {
                $leaveService->ensureEmployeeBalances($employee, $year);
                $total++;
            }

            $this->line("Allocated {$employees->count()} employee(s) for {$factory->name} ({$year}).");
        }

        $this->info("Leave allocation complete — {$total} employee record(s) processed.");

        return self::SUCCESS;
    }
}
