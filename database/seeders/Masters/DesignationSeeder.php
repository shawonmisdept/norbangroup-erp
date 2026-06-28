<?php

namespace Database\Seeders\Masters;

use App\Models\Department;
use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['name' => 'General Manager', 'department' => 'Production Planning'],
            ['name' => 'Merchandiser', 'department' => 'Merchandising'],
            ['name' => 'Senior Merchandiser', 'department' => 'Merchandising'],
            ['name' => 'Production Manager', 'department' => 'Sewing'],
            ['name' => 'Knitting Manager', 'department' => 'Knitting'],
            ['name' => 'Dyeing Manager', 'department' => 'Dyeing'],
            ['name' => 'Line Supervisor', 'department' => 'Sewing'],
            ['name' => 'QC Manager', 'department' => 'Quality Control'],
            ['name' => 'QC Inspector', 'department' => 'Quality Control'],
            ['name' => 'Cutting Master', 'department' => 'Cutting'],
            ['name' => 'Machine Operator', 'department' => 'Sewing'],
        ];

        foreach ($records as $record) {
            $departmentId = $record['department']
                ? Department::where('name', $record['department'])->value('id')
                : null;

            Designation::updateOrCreate(
                [
                    'name'          => $record['name'],
                    'department_id' => $departmentId,
                ],
                [
                    'is_active' => true,
                ]
            );
        }
    }
}
