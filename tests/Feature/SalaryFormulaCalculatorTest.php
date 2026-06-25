<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryGradeDetail;
use App\Models\Hrm\SalaryHead;
use App\Services\Hrm\SalaryFormulaCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalaryFormulaCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_sr01_grade_calculates_from_gross(): void
    {
        $factory = Factory::create(['name' => 'Test Factory', 'is_active' => true]);

        $heads = collect([
            'GROSS', 'BASIC', 'HOUSE RENT', 'CONVEYANCE', 'FOOD ALLOWANCE', 'MEDICAL',
        ])->mapWithKeys(fn ($code) => [
            $code => SalaryHead::create([
                'factory_id' => $factory->id,
                'code'       => $code,
                'name'       => $code,
                'head_type'  => 'E',
                'sort_order' => 1,
                'is_active'  => true,
            ])->id,
        ]);

        $grade = SalaryGrade::create([
            'factory_id' => $factory->id,
            'code'       => 'SR-01',
            'name'       => 'SR-01',
            'is_active'  => true,
        ]);

        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $heads['GROSS'], 'detail_type' => 'F', 'is_fixed' => false, 'amount' => 0]);
        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $heads['CONVEYANCE'], 'detail_type' => 'F', 'is_fixed' => true, 'amount' => 200]);
        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $heads['FOOD ALLOWANCE'], 'detail_type' => 'F', 'is_fixed' => true, 'amount' => 650]);
        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $heads['MEDICAL'], 'detail_type' => 'F', 'is_fixed' => true, 'amount' => 250]);
        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $heads['BASIC'], 'detail_type' => 'M', 'is_fixed' => false, 'formula' => '(<GROSS>-(<MEDICAL>+<FOOD ALLOWANCE>+<CONVEYANCE>))/1.4']);
        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $heads['HOUSE RENT'], 'detail_type' => 'P', 'is_fixed' => false, 'percentage' => 40, 'percentage_of_head_id' => $heads['BASIC']]);

        $amounts = app(SalaryFormulaCalculator::class)->calculate($grade, 28000);

        $this->assertEquals(28000, $amounts['GROSS']);
        $this->assertEquals(200, $amounts['CONVEYANCE']);
        $this->assertEquals(650, $amounts['FOOD ALLOWANCE']);
        $this->assertEquals(250, $amounts['MEDICAL']);
        $this->assertEqualsWithDelta(19214.29, $amounts['BASIC'], 0.02);
        $this->assertEqualsWithDelta(7685.71, $amounts['HOUSE RENT'], 0.02);
    }
}
