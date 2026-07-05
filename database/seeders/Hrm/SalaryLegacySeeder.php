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
        $factory = Factory::where('name', 'Head Office')->where('is_active', true)->first();

        if (! $factory) {
            $this->command?->warn('Head Office not found.');

            return;
        }

        SalaryHead::where('factory_id', $factory->id)->where('head_type', 'earning')->update(['head_type' => 'E']);
        SalaryHead::where('factory_id', $factory->id)->where('head_type', 'deduction')->update(['head_type' => 'D']);

        $heads = [
            ['code' => 'GROSS', 'name' => 'Gross', 'head_type' => 'E', 'sort_order' => 1, 'description' => 'Total monthly salary'],
            ['code' => 'BASIC', 'name' => 'Basic', 'head_type' => 'E', 'sort_order' => 2, 'description' => 'Basic salary'],
            ['code' => 'HOUSE RENT', 'name' => 'House Rent', 'head_type' => 'E', 'sort_order' => 3],
            ['code' => 'MEDICAL', 'name' => 'Medical', 'head_type' => 'E', 'sort_order' => 4],
            ['code' => 'OTHER ALLOWANCE', 'name' => 'Other Allowance', 'head_type' => 'E', 'sort_order' => 5],
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
            ['head' => 'BASIC', 'detail_type' => 'P', 'is_fixed' => false, 'percentage' => 50, 'parent' => 'GROSS'],
            ['head' => 'HOUSE RENT', 'detail_type' => 'P', 'is_fixed' => false, 'percentage' => 25, 'parent' => 'BASIC'],
            ['head' => 'MEDICAL', 'detail_type' => 'P', 'is_fixed' => false, 'percentage' => 10, 'parent' => 'BASIC'],
            ['head' => 'OTHER ALLOWANCE', 'detail_type' => 'P', 'is_fixed' => false, 'percentage' => 15, 'parent' => 'BASIC'],
        ];

        $activeHeadIds = [];

        foreach ($details as $row) {
            $activeHeadIds[] = $headIds[$row['head']];

            SalaryGradeDetail::updateOrCreate(
                ['salary_grade_id' => $grade->id, 'salary_head_id' => $headIds[$row['head']]],
                [
                    'detail_type'            => $row['detail_type'],
                    'is_fixed'               => $row['is_fixed'],
                    'amount'                 => $row['amount'] ?? 0,
                    'percentage'             => $row['percentage'] ?? null,
                    'percentage_of_head_id'  => isset($row['parent']) ? $headIds[$row['parent']] : null,
                    'formula'                => null,
                ]
            );
        }

        SalaryGradeDetail::query()
            ->where('salary_grade_id', $grade->id)
            ->whereNotIn('salary_head_id', $activeHeadIds)
            ->delete();

        SalaryHead::query()
            ->where('factory_id', $factory->id)
            ->whereNotIn('id', array_values($headIds))
            ->update(['is_active' => false]);

        $this->command?->info("Seeded SR-01 salary grade with standard heads for {$factory->name}.");
    }
}
