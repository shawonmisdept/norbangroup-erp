<?php

namespace Database\Seeders\Hrm;

use App\Models\Factory;
use App\Models\Hrm\LeavePolicy;
use App\Models\Hrm\LeaveType;
use Illuminate\Database\Seeder;

class LeavePolicySeeder extends Seeder
{
    public function run(): void
    {
        $factory = Factory::where('name', 'Head Office')->where('is_active', true)->first();

        if (! $factory) {
            $this->command?->warn('Head Office not found — skipping leave policy seed.');

            return;
        }

        /** @var array<int, array{leave_type: string, days_per_year: float|int, min_days_notice: int}> $policies */
        $policies = require database_path('seeders/data/hrm_leave_policies.php');

        $activeLeaveTypeIds = [];

        foreach ($policies as $row) {
            $leaveType = LeaveType::where('name', $row['leave_type'])->where('is_active', true)->first();

            if (! $leaveType) {
                $this->command?->warn("Leave type \"{$row['leave_type']}\" not found — policy skipped.");

                continue;
            }

            $activeLeaveTypeIds[] = $leaveType->id;

            LeavePolicy::updateOrCreate(
                ['factory_id' => $factory->id, 'leave_type_id' => $leaveType->id],
                [
                    'days_per_year'               => $row['days_per_year'],
                    'min_days_notice'             => $row['min_days_notice'],
                    'requires_medical_after_days' => null,
                    'requires_attachment'         => false,
                    'is_active'                   => true,
                ]
            );
        }

        LeavePolicy::query()
            ->where('factory_id', $factory->id)
            ->whereNotIn('leave_type_id', $activeLeaveTypeIds)
            ->delete();

        $this->command?->info('Seeded ' . count($activeLeaveTypeIds) . " leave polic(ies) for {$factory->name}.");
    }
}
