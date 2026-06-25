<?php

namespace Database\Seeders\Hrm;

use App\Models\Hrm\LeaveType;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return LeaveType::class;
    }

    protected function records(): array
    {
        return $this->recordsFromDataFile('hrm_leave_types.php');
    }
}
