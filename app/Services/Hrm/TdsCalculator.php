<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\SalaryHead;
use App\Models\Hrm\SalaryStructure;
use App\Models\Hrm\TaxSlab;
use App\Models\Hrm\TaxYear;
use Carbon\Carbon;

class TdsCalculator
{
    public function activeTaxYear(int $factoryId, Carbon $date): ?TaxYear
    {
        return TaxYear::query()
            ->where('factory_id', $factoryId)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->with('slabs')
            ->first();
    }

    public function monthlyTds(
        Employee $employee,
        float $grossPay,
        float $basicAmount,
        SalaryStructure $structure,
        Carbon $date
    ): array {
        $taxYear = $this->activeTaxYear($employee->factory_id, $date);

        if (! $taxYear || $taxYear->slabs->isEmpty()) {
            return ['taxable_income' => 0.0, 'tds_amount' => 0.0, 'tax_year_id' => null];
        }

        $taxable = $this->resolveTaxableIncome($employee->factory_id, $structure, $grossPay, $basicAmount);
        $annualTaxable = $taxable * 12;
        $annualTax = $this->taxOnIncome($taxYear->slabs, $annualTaxable);
        $monthlyTds = round($annualTax / 12, 2);

        return [
            'taxable_income' => round($taxable, 2),
            'tds_amount'     => $monthlyTds,
            'tax_year_id'    => $taxYear->id,
        ];
    }

    private function resolveTaxableIncome(
        int $factoryId,
        SalaryStructure $structure,
        float $grossPay,
        float $basicAmount
    ): float {
        if (! $structure->head_amounts) {
            return $basicAmount > 0 ? $basicAmount : $grossPay;
        }

        $taxableCodes = SalaryHead::query()
            ->where('factory_id', $factoryId)
            ->where('is_active', true)
            ->where('is_taxable', true)
            ->pluck('code')
            ->map(fn ($c) => strtoupper(trim($c)));

        $total = 0.0;

        foreach ($taxableCodes as $code) {
            $total += $structure->headAmount($code);
        }

        if ($total <= 0) {
            return $basicAmount > 0 ? $basicAmount : $grossPay;
        }

        return $total;
    }

    /** @param \Illuminate\Support\Collection<int, TaxSlab> $slabs */
    private function taxOnIncome($slabs, float $annualIncome): float
    {
        if ($annualIncome <= 0) {
            return 0.0;
        }

        $tax = 0.0;

        foreach ($slabs as $slab) {
            $min = (float) $slab->min_income;
            $max = $slab->max_income !== null ? (float) $slab->max_income : PHP_FLOAT_MAX;

            if ($annualIncome <= $min) {
                continue;
            }

            $band = min($annualIncome, $max) - $min;
            $tax += $band * ((float) $slab->rate_percent / 100);
        }

        return max(0, round($tax, 2));
    }
}
