<?php

namespace App\Services\Hrm;

use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RmgExportService
{
    public function __construct(private StatutoryRegisterService $registers) {}

    public function cashListCsv(PayrollPeriod $period): StreamedResponse
    {
        $items = PayrollItem::query()
            ->with(['employee.line', 'employee.designation'])
            ->where('payroll_period_id', $period->id)
            ->where('cash_pay_amount', '>', 0)
            ->get()
            ->sortBy(fn ($item) => ($item->employee?->line?->name ?? '') . ($item->employee?->name ?? ''));

        return response()->streamDownload(function () use ($items) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Line', 'Employee Code', 'Name', 'Designation', 'Cash Pay']);
            foreach ($items as $item) {
                fputcsv($out, [
                    $item->employee?->line?->name ?? '—',
                    $item->employee?->employee_code,
                    $item->employee?->name,
                    $item->employee?->designation?->name ?? '—',
                    number_format((float) $item->cash_pay_amount, 2, '.', ''),
                ]);
            }
            fclose($out);
        }, 'cash-list-' . $period->periodLabel() . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function buyerAuditCsv(int $factoryId, int $year, int $month): StreamedResponse
    {
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $attendance = $this->registers->attendanceRegister($factoryId, $from, $to)->values()->all();
        $wages = $this->registers->wageRegister($factoryId, $year, $month)->values()->all();

        return response()->streamDownload(function () use ($attendance, $wages, $year, $month) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Buyer Audit Pack — ' . sprintf('%04d-%02d', $year, $month)]);
            fputcsv($out, []);
            fputcsv($out, ['--- Attendance Register ---']);
            if ($attendance !== []) {
                fputcsv($out, array_keys($attendance[0]));
                foreach ($attendance as $row) {
                    fputcsv($out, $row);
                }
            }
            fputcsv($out, []);
            fputcsv($out, ['--- Wage Register ---']);
            if ($wages !== []) {
                fputcsv($out, array_keys($wages[0]));
                foreach ($wages as $row) {
                    fputcsv($out, $row);
                }
            }
            fclose($out);
        }, 'buyer-audit-' . $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
