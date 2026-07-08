<?php

namespace App\Services\Hrm;

use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\SalaryBank;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BankLedgerService
{
    public const UNASSIGNED_KEY = 'unassigned';

    public const UNASSIGNED_LABEL = 'Unassigned Bank';

    /** @return Collection<int, PayrollPeriod> */
    public function frozenPeriodOptions(?int $factoryId = null): Collection
    {
        return PayrollPeriod::query()
            ->when($factoryId, fn ($q) => $q->where('factory_id', $factoryId))
            ->where('status', 'frozen')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();
    }

    /** @return array<int, string> */
    public function bankFilterOptions(?int $factoryId = null): array
    {
        $banks = SalaryBank::query()
            ->when($factoryId, fn ($q) => $q->where('factory_id', $factoryId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);

        $options = $banks->mapWithKeys(fn (SalaryBank $bank) => [
            (string) $bank->id => $bank->displayName(),
        ])->all();

        $options[self::UNASSIGNED_KEY] = self::UNASSIGNED_LABEL;

        return $options;
    }

    /** @param  array{factory_id?: int|null, payroll_period_id?: int|null, salary_bank_id?: string|null, search?: string|null}  $filters */
    public function summaryRows(array $filters): Collection
    {
        $rows = $this->baseQuery($filters)
            ->selectRaw('
                salary_bank_id,
                COUNT(*) as headcount,
                SUM(bank_pay_amount) as bank_total,
                SUM(cash_pay_amount) as cash_total,
                SUM(net_pay) as net_total
            ')
            ->groupBy('salary_bank_id')
            ->orderByRaw('salary_bank_id IS NULL')
            ->orderBy('salary_bank_id')
            ->get();

        $bankNames = SalaryBank::query()
            ->whereIn('id', $rows->pluck('salary_bank_id')->filter())
            ->pluck('name', 'id');

        return $rows->map(function ($row) use ($bankNames) {
            $bankId = $row->salary_bank_id;

            return [
                'bank_key'     => $bankId ? (string) $bankId : self::UNASSIGNED_KEY,
                'bank_id'      => $bankId,
                'bank_name'    => $bankId
                    ? ($bankNames[$bankId] ?? 'Bank #' . $bankId)
                    : self::UNASSIGNED_LABEL,
                'headcount'    => (int) $row->headcount,
                'bank_total'   => (float) $row->bank_total,
                'cash_total'   => (float) $row->cash_total,
                'net_total'    => (float) $row->net_total,
                'is_unassigned'=> $bankId === null,
            ];
        });
    }

    /** @param  array{factory_id?: int|null, payroll_period_id?: int|null, salary_bank_id?: string|null, search?: string|null}  $filters */
    public function summaryTotals(Collection $rows): array
    {
        return [
            'headcount'  => (int) $rows->sum('headcount'),
            'bank_total' => round((float) $rows->sum('bank_total'), 2),
            'cash_total' => round((float) $rows->sum('cash_total'), 2),
            'net_total'  => round((float) $rows->sum('net_total'), 2),
        ];
    }

    /** @param  array{factory_id?: int|null, payroll_period_id?: int|null, salary_bank_id?: string|null, search?: string|null}  $filters */
    public function detailPaginator(array $filters, int $perPage = 30): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->with(['employee', 'salaryBank', 'period'])
            ->orderBy('salary_bank_id')
            ->orderBy('employee_id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function unassignedCount(array $filters): int
    {
        $filters = array_merge($filters, ['salary_bank_id' => self::UNASSIGNED_KEY]);

        return (int) $this->baseQuery($filters)->count();
    }

    /** @param  array{factory_id?: int|null, payroll_period_id?: int|null, salary_bank_id?: string|null, search?: string|null}  $filters */
    public function exportSummaryCsv(array $filters, ?PayrollPeriod $period = null): StreamedResponse
    {
        $rows = $this->summaryRows($filters);
        $totals = $this->summaryTotals($rows);
        $label = $this->exportFilenameLabel($filters, $period);

        return response()->streamDownload(function () use ($rows, $totals, $period) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Bank Payment Register — Summary']);
            if ($period) {
                fputcsv($out, ['Period', $period->periodLabel()]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Bank', 'Employees', 'Bank Pay', 'Cash Pay', 'Net Pay']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['bank_name'],
                    $row['headcount'],
                    number_format($row['bank_total'], 2, '.', ''),
                    number_format($row['cash_total'], 2, '.', ''),
                    number_format($row['net_total'], 2, '.', ''),
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, [
                'Grand Total',
                $totals['headcount'],
                number_format($totals['bank_total'], 2, '.', ''),
                number_format($totals['cash_total'], 2, '.', ''),
                number_format($totals['net_total'], 2, '.', ''),
            ]);
            fclose($out);
        }, 'bank-ledger-summary-' . $label . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @param  array{factory_id?: int|null, payroll_period_id?: int|null, salary_bank_id?: string|null, search?: string|null}  $filters */
    public function exportDetailCsv(array $filters, ?PayrollPeriod $period = null): StreamedResponse
    {
        $label = $this->exportFilenameLabel($filters, $period);

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Period', 'Bank', 'Employee Code', 'Employee Name', 'Account Number', 'Bank Pay', 'Cash Pay', 'Net Pay']);

            $this->baseQuery($filters)
                ->with(['employee', 'salaryBank', 'period'])
                ->orderBy('salary_bank_id')
                ->orderBy('employee_id')
                ->chunk(100, function ($items) use ($out) {
                    foreach ($items as $item) {
                        fputcsv($out, [
                            $item->period?->periodLabel() ?? '',
                            $item->salaryBank?->displayName() ?? self::UNASSIGNED_LABEL,
                            $item->employee?->employee_code,
                            $item->employee?->name,
                            $item->bank_account ?? '',
                            number_format((float) $item->bank_pay_amount, 2, '.', ''),
                            number_format((float) $item->cash_pay_amount, 2, '.', ''),
                            number_format((float) $item->net_pay, 2, '.', ''),
                        ]);
                    }
                });

            fclose($out);
        }, 'bank-ledger-detail-' . $label . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @param  array{factory_id?: int|null, payroll_period_id?: int|null, salary_bank_id?: string|null, search?: string|null}  $filters */
    private function baseQuery(array $filters): Builder
    {
        $query = PayrollItem::query()
            ->where('bank_pay_amount', '>', 0)
            ->whereHas('period', fn ($q) => $q->where('status', 'frozen'));

        if (! empty($filters['factory_id'])) {
            $query->where('factory_id', (int) $filters['factory_id']);
        }

        if (! empty($filters['payroll_period_id'])) {
            $query->where('payroll_period_id', (int) $filters['payroll_period_id']);
        }

        if (! empty($filters['salary_bank_id'])) {
            if ($filters['salary_bank_id'] === self::UNASSIGNED_KEY) {
                $query->whereNull('salary_bank_id');
            } else {
                $query->where('salary_bank_id', (int) $filters['salary_bank_id']);
            }
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%"));
        }

        return $query;
    }

    /** @param  array{factory_id?: int|null, payroll_period_id?: int|null}  $filters */
    private function exportFilenameLabel(array $filters, ?PayrollPeriod $period): string
    {
        if ($period) {
            return str_replace(' ', '-', strtolower($period->periodLabel()));
        }

        if (! empty($filters['factory_id'])) {
            return 'factory-' . $filters['factory_id'];
        }

        return 'all';
    }
}
