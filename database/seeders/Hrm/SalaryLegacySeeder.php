<?php

namespace Database\Seeders\Hrm;

use App\Models\Factory;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryGradeDetail;
use App\Models\Hrm\SalaryHead;
use Illuminate\Database\Seeder;

class SalaryLegacySeeder extends Seeder
{
    public function run(): void
    {
        $factory = Factory::where('name', 'Norban Comtex Limited')->where('is_active', true)->first();

        if (! $factory) {
            $this->command?->warn('Norban Comtex Limited not found.');

            return;
        }

        SalaryHead::where('factory_id', $factory->id)->where('head_type', 'earning')->update(['head_type' => 'E']);
        SalaryHead::where('factory_id', $factory->id)->where('head_type', 'deduction')->update(['head_type' => 'D']);

        $heads = [
            ['code' => 'GROSS', 'name' => 'Gross', 'head_type' => 'E', 'sort_order' => 1, 'description' => 'Total monthly salary'],
            ['code' => 'BASIC', 'name' => 'Basic', 'head_type' => 'E', 'sort_order' => 2, 'description' => 'Basic salary'],
            ['code' => 'HOUSE RENT', 'name' => 'House Rent', 'head_type' => 'E', 'sort_order' => 3],
            ['code' => 'CONVEYANCE', 'name' => 'Conveyance', 'head_type' => 'E', 'sort_order' => 4],
            ['code' => 'FOOD ALLOWANCE', 'name' => 'Food Allowance', 'head_type' => 'E', 'sort_order' => 5],
            ['code' => 'MEDICAL', 'name' => 'Medical', 'head_type' => 'E', 'sort_order' => 6],
            ['code' => 'PERFORMANCE BONUS', 'name' => 'Performance Bonus', 'head_type' => 'E', 'sort_order' => 7],
            ['code' => 'STAMP', 'name' => 'Stamp', 'head_type' => 'D', 'sort_order' => 20],
            ['code' => 'ABSENTEEISM', 'name' => 'Absenteeism', 'head_type' => 'D', 'sort_order' => 21],
        ];

        $headIds = [];

        foreach ($heads as $head) {
            $model = SalaryHead::updateOrCreate(
                ['factory_id' => $factory->id, 'code' => $head['code']],
                array_merge($head, ['factory_id' => $factory->id, 'is_active' => true, 'is_disburse' => true])
            );
            $headIds[$head['code']] = $model->id;
        }

        $grade = SalaryGrade::updateOrCreate(
            ['factory_id' => $factory->id, 'code' => 'SR-01'],
            ['name' => 'SR-01', 'description' => 'Standard staff salary grade', 'is_active' => true]
        );

        $details = [
            ['head' => 'GROSS', 'detail_type' => 'F', 'is_fixed' => false, 'amount' => 0],
            ['head' => 'BASIC', 'detail_type' => 'M', 'is_fixed' => false, 'formula' => '(<GROSS>-(<MEDICAL>+<FOOD ALLOWANCE>+<CONVEYANCE>))/1.4'],
            ['head' => 'CONVEYANCE', 'detail_type' => 'F', 'is_fixed' => true, 'amount' => 200],
            ['head' => 'FOOD ALLOWANCE', 'detail_type' => 'F', 'is_fixed' => true, 'amount' => 650],
            ['head' => 'MEDICAL', 'detail_type' => 'F', 'is_fixed' => true, 'amount' => 250],
            ['head' => 'HOUSE RENT', 'detail_type' => 'P', 'is_fixed' => false, 'percentage' => 40, 'parent' => 'BASIC'],
            ['head' => 'STAMP', 'detail_type' => 'F', 'is_fixed' => true, 'amount' => 20],
            ['head' => 'PERFORMANCE BONUS', 'detail_type' => 'F', 'is_fixed' => true, 'amount' => 0],
        ];

        foreach ($details as $row) {
            SalaryGradeDetail::updateOrCreate(
                ['salary_grade_id' => $grade->id, 'salary_head_id' => $headIds[$row['head']]],
                [
                    'detail_type'            => $row['detail_type'],
                    'is_fixed'               => $row['is_fixed'],
                    'amount'                 => $row['amount'] ?? 0,
                    'percentage'             => $row['percentage'] ?? null,
                    'percentage_of_head_id'  => isset($row['parent']) ? $headIds[$row['parent']] : null,
                    'formula'                => $row['formula'] ?? null,
                ]
            );
        }

        $this->command?->info("Seeded SR-01 salary grade with legacy heads for {$factory->name}.");
    }
}
