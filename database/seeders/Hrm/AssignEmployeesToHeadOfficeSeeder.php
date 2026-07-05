<?php

namespace Database\Seeders\Hrm;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\Hrm\Building;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Floor;
use App\Models\Hrm\Line;
use App\Models\Hrm\SalaryStructure;
use App\Models\Hrm\Shift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssignEmployeesToHeadOfficeSeeder extends Seeder
{
    private const HEAD_OFFICE_NAME = 'Head Office';

    /** @var list<string> */
    private array $employeeFactoryTables = [
        'hrm_salary_structures',
        'hrm_attendance_daily_logs',
        'hrm_attendance_raw_punches',
        'hrm_payroll_items',
        'hrm_leave_applications',
        'hrm_leave_balances',
        'hrm_late_acceptance_applications',
        'hrm_loan_accounts',
        'hrm_pf_accounts',
        'hrm_employee_tax_ledgers',
        'hrm_employee_service_histories',
        'hrm_employee_separations',
        'hrm_employee_promotions',
        'hrm_final_settlements',
        'hrm_gratuity_settlements',
        'hrm_disciplinary_records',
        'hrm_issued_letters',
        'hrm_performance_reviews',
        'hrm_maternity_transactions',
        'hrm_canteen_deductions',
        'hrm_salary_holds',
        'hrm_salary_increment_logs',
        'hrm_training_records',
        'hrm_medical_visits',
    ];

    public function run(): void
    {
        $headOffice = Factory::query()
            ->where('name', self::HEAD_OFFICE_NAME)
            ->where('is_active', true)
            ->first();

        if (! $headOffice) {
            $this->command?->warn('Head Office factory not found — skipping employee unit assignment.');

            return;
        }

        $moved = 0;

        Employee::query()
            ->with(['department', 'designation', 'building', 'floor', 'line', 'shift'])
            ->where('factory_id', '!=', $headOffice->id)
            ->each(function (Employee $employee) use ($headOffice, &$moved) {
                $departmentId = $this->remapDepartment($employee->department_id, $headOffice);
                $designationId = $this->remapDesignation($employee->designation_id, $departmentId);
                $buildingId = $this->remapBuilding($employee->building_id, $headOffice);
                $floorId = $this->remapFloor($employee->floor_id, $buildingId);
                $lineId = $this->remapLine($employee->line_id, $floorId, $headOffice);
                $shiftId = $this->remapShift($employee->shift_id, $headOffice);

                $employee->update([
                    'factory_id'     => $headOffice->id,
                    'department_id'  => $departmentId,
                    'designation_id' => $designationId,
                    'building_id'    => $buildingId,
                    'floor_id'       => $floorId,
                    'line_id'        => $lineId,
                    'shift_id'       => $shiftId,
                ]);

                $moved++;
            });

        $employeeIds = Employee::pluck('id');

        if ($employeeIds->isNotEmpty()) {
            SalaryStructure::query()
                ->whereIn('employee_id', $employeeIds)
                ->update(['factory_id' => $headOffice->id]);

            foreach ($this->employeeFactoryTables as $table) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'employee_id') || ! Schema::hasColumn($table, 'factory_id')) {
                    continue;
                }

                DB::table($table)
                    ->whereIn('employee_id', $employeeIds)
                    ->update(['factory_id' => $headOffice->id]);
            }
        }

        $this->command?->info("Assigned all employees to Head Office ({$moved} moved, {$employeeIds->count()} total).");
    }

    private function remapDepartment(?int $departmentId, Factory $headOffice): ?int
    {
        if (! $departmentId) {
            return null;
        }

        $department = Department::find($departmentId);

        if (! $department) {
            return null;
        }

        if ((int) $department->factory_id === (int) $headOffice->id) {
            return $department->id;
        }

        return Department::query()
            ->where('factory_id', $headOffice->id)
            ->where('name', $department->name)
            ->value('id');
    }

    private function remapDesignation(?int $designationId, ?int $departmentId): ?int
    {
        if (! $designationId || ! $departmentId) {
            return null;
        }

        $designation = Designation::find($designationId);

        if (! $designation) {
            return null;
        }

        if ((int) $designation->department_id === $departmentId) {
            return $designation->id;
        }

        return Designation::query()
            ->where('department_id', $departmentId)
            ->where('name', $designation->name)
            ->value('id');
    }

    private function remapBuilding(?int $buildingId, Factory $headOffice): ?int
    {
        if (! $buildingId) {
            return null;
        }

        $building = Building::find($buildingId);

        if (! $building) {
            return null;
        }

        if ((int) $building->factory_id === (int) $headOffice->id) {
            return $building->id;
        }

        return Building::query()
            ->where('factory_id', $headOffice->id)
            ->where('name', $building->name)
            ->value('id');
    }

    private function remapFloor(?int $floorId, ?int $buildingId): ?int
    {
        if (! $floorId || ! $buildingId) {
            return null;
        }

        $floor = Floor::find($floorId);

        if (! $floor) {
            return null;
        }

        if ((int) $floor->building_id === $buildingId) {
            return $floor->id;
        }

        return Floor::query()
            ->where('building_id', $buildingId)
            ->where('name', $floor->name)
            ->value('id');
    }

    private function remapLine(?int $lineId, ?int $floorId, Factory $headOffice): ?int
    {
        if (! $lineId) {
            return null;
        }

        $line = Line::find($lineId);

        if (! $line) {
            return null;
        }

        if ($floorId && (int) $line->floor_id === $floorId) {
            return $line->id;
        }

        if ((int) $line->factory_id === (int) $headOffice->id) {
            return $line->id;
        }

        if (! $floorId) {
            return null;
        }

        return Line::query()
            ->where('floor_id', $floorId)
            ->where('name', $line->name)
            ->value('id');
    }

    private function remapShift(?int $shiftId, Factory $headOffice): ?int
    {
        if (! $shiftId) {
            return null;
        }

        $shift = Shift::find($shiftId);

        if (! $shift) {
            return null;
        }

        if ((int) $shift->factory_id === (int) $headOffice->id) {
            return $shift->id;
        }

        return Shift::query()
            ->where('factory_id', $headOffice->id)
            ->where('name', $shift->name)
            ->value('id');
    }
}
