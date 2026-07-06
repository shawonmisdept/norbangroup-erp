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

    public function run(): void
    {
        $records = $this->records();
        $activeNames = collect($records)->pluck('name')->all();
        $model = $this->modelClass();

        foreach ($records as $record) {
            $attributes = array_merge(['is_active' => true], $record);

            $model::updateOrCreate(
                ['name' => $attributes['name']],
                $attributes
            );
        }

        LeaveType::query()
            ->whereNotIn('name', $activeNames)
            ->update(['is_active' => false]);
    }
}
