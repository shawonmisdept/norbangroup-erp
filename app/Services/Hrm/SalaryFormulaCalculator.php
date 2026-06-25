<?php

namespace App\Services\Hrm;

use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryGradeDetail;
use App\Models\Hrm\SalaryHead;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class SalaryFormulaCalculator
{
    /** @var array<string, float> */
    private array $amounts = [];

    /** @var Collection<int, SalaryGradeDetail> */
    private Collection $details;

    /** @var array<string, string> code => name */
    private array $headCodes = [];

    /**
     * @param  array<string, float>  $overrides  head code => amount (employee-level fixed overrides)
     * @return array<string, float> head code => calculated amount
     */
    public function calculate(SalaryGrade $grade, float $gross, array $overrides = []): array
    {
        $this->details = SalaryGradeDetail::query()
            ->where('salary_grade_id', $grade->id)
            ->with(['salaryHead', 'percentageOfHead'])
            ->get();

        $heads = SalaryHead::query()
            ->where('factory_id', $grade->factory_id)
            ->where('is_active', true)
            ->get(['id', 'code', 'name']);

        $this->headCodes = $heads->mapWithKeys(fn (SalaryHead $h) => [
            strtoupper(trim($h->code)) => strtoupper(trim($h->name)),
            strtoupper(trim($h->name))  => strtoupper(trim($h->name)),
        ])->all();

        $this->amounts = [];

        foreach ($heads as $head) {
            $this->amounts[strtoupper(trim($head->code))] = 0.0;
        }

        $this->amounts['GROSS'] = round($gross, 2);

        foreach ($overrides as $code => $value) {
            $key = strtoupper(trim((string) $code));
            if ($key !== '') {
                $this->amounts[$key] = round((float) $value, 2);
            }
        }

        $this->applyFixedDetails($overrides);
        $this->applyFormulaDetails();
        $this->applyPercentageDetails();

        return $this->amounts;
    }

    /** @param array<string, float> $overrides */
    private function applyFixedDetails(array $overrides): void
    {
        foreach ($this->details->where('detail_type', 'F') as $detail) {
            $code = $this->headCode($detail);

            if ($code === 'GROSS' || ! $detail->is_fixed) {
                continue;
            }

            if (array_key_exists($code, $overrides)) {
                continue;
            }

            $this->amounts[$code] = round((float) $detail->amount, 2);
        }
    }

    private function applyFormulaDetails(): void
    {
        $formulaDetails = $this->details->where('detail_type', 'M')->filter(fn ($d) => filled($d->formula));

        for ($pass = 0; $pass < 8; $pass++) {
            $changed = false;

            foreach ($formulaDetails as $detail) {
                $code = $this->headCode($detail);
                $result = $this->evaluateFormula($detail->formula);

                if ($this->amounts[$code] !== $result) {
                    $this->amounts[$code] = $result;
                    $changed = true;
                }
            }

            if (! $changed) {
                break;
            }
        }
    }

    private function applyPercentageDetails(): void
    {
        for ($pass = 0; $pass < 4; $pass++) {
            foreach ($this->details->where('detail_type', 'P') as $detail) {
                if (! $detail->percentage_of_head_id || $detail->percentage === null) {
                    continue;
                }

                $parentCode = strtoupper(trim($detail->percentageOfHead?->code ?? ''));
                $code = $this->headCode($detail);
                $parentAmount = $this->amounts[$parentCode] ?? 0;
                $this->amounts[$code] = round($parentAmount * ((float) $detail->percentage / 100), 2);
            }
        }
    }

    private function evaluateFormula(string $formula): float
    {
        $expression = preg_replace_callback('/<([^>]+)>/', function (array $matches) {
            $token = strtoupper(trim($matches[1]));
            $code = $this->resolveToken($token);

            return (string) ($this->amounts[$code] ?? 0);
        }, $formula) ?? '0';

        $expression = trim($expression);

        if ($expression === '') {
            return 0.0;
        }

        if (! preg_match('/^[\d\s+\-*\/().]+$/', $expression)) {
            throw new InvalidArgumentException('Invalid formula expression.');
        }

        /** @var float|int $result */
        $result = eval('return (' . $expression . ');');

        return round((float) $result, 2);
    }

    private function resolveToken(string $token): string
    {
        if (array_key_exists($token, $this->amounts)) {
            return $token;
        }

        foreach ($this->amounts as $code => $_) {
            if ($code === $token) {
                return $code;
            }
        }

        foreach ($this->headCodes as $alias => $canonical) {
            if ($alias === $token || $canonical === $token) {
                foreach ($this->amounts as $code => $_) {
                    if ($code === $alias || strtoupper($this->headCodes[$code] ?? '') === $token) {
                        return $code;
                    }
                }
            }
        }

        return $token;
    }

    private function headCode(SalaryGradeDetail $detail): string
    {
        return strtoupper(trim($detail->salaryHead?->code ?? ''));
    }
}
