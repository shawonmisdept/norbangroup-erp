<?php

namespace App\Http\Controllers\Admin\Hrm\Compliance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Services\Hrm\StatutoryRegisterService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegisterController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $factories = $this->factoryOptions($request);
        $factoryId = $this->resolveFactoryFilterFromRequest($request, $factories);
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        return view('admin.hrm.compliance.registers.index', [
            'factories' => $factories,
            'factoryId' => $factoryId,
            'year'      => $year,
            'month'     => $month,
            'filters'   => $request->only(['factory_id', 'year', 'month']),
        ]);
    }

    public function export(Request $request, StatutoryRegisterService $registers, string $type): StreamedResponse
    {
        $factoryId = $this->requireFactoryFilter(
            $request,
            $request->filled('factory_id') ? (int) $request->factory_id : null,
        );

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $filename = sprintf('statutory-%s-%d-%02d-%d.csv', $type, $factoryId, $year, $month);

        return response()->streamDownload(function () use ($registers, $type, $factoryId, $from, $to, $year, $month) {
            $handle = fopen('php://output', 'w');

            match ($type) {
                'attendance' => $this->exportAttendance($handle, $registers, $factoryId, $from, $to),
                'wage'       => $this->exportWage($handle, $registers, $factoryId, $year, $month),
                'leave'      => $this->exportLeave($handle, $registers, $factoryId, $from, $to),
                'ot'         => $this->exportOt($handle, $registers, $factoryId, $year, $month),
                default      => fputcsv($handle, ['Invalid register type']),
            };

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function exportAttendance($handle, StatutoryRegisterService $registers, int $factoryId, Carbon $from, Carbon $to): void
    {
        fputcsv($handle, ['Date', 'Employee Code', 'Employee Name', 'Department', 'Status', 'Check In', 'Check Out', 'Work Hours', 'Late Minutes']);

        foreach ($registers->attendanceRegister($factoryId, $from, $to) as $row) {
            fputcsv($handle, array_values($row));
        }
    }

    private function exportWage($handle, StatutoryRegisterService $registers, int $factoryId, int $year, int $month): void
    {
        fputcsv($handle, ['Employee Code', 'Name', 'Category', 'Pay Type', 'Present', 'Absent', 'Leave', 'OT Hrs', 'Basic', 'Allowances', 'OT Amt', 'Gross', 'Deductions', 'Net Pay']);

        foreach ($registers->wageRegister($factoryId, $year, $month) as $row) {
            fputcsv($handle, array_values($row));
        }
    }

    private function exportLeave($handle, StatutoryRegisterService $registers, int $factoryId, Carbon $from, Carbon $to): void
    {
        fputcsv($handle, ['Employee Code', 'Name', 'Leave Type', 'Start', 'End', 'Days', 'Status']);

        foreach ($registers->leaveRegister($factoryId, $from, $to) as $row) {
            fputcsv($handle, array_values($row));
        }
    }

    private function exportOt($handle, StatutoryRegisterService $registers, int $factoryId, int $year, int $month): void
    {
        fputcsv($handle, ['Employee Code', 'Name', 'OT Hours', 'OT Amount', 'Breakdown']);

        foreach ($registers->otRegister($factoryId, $year, $month) as $row) {
            fputcsv($handle, array_values($row));
        }
    }
}
