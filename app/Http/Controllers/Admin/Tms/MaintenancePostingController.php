<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Services\Tms\MaintenancePostingReportService;
use App\Services\Tms\MaintenanceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaintenancePostingController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(
        private MaintenancePostingReportService $postingReport,
        private MaintenanceService $maintenanceService,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'factory_id' => ['nullable', 'exists:factories,id'],
            'workshop'   => ['nullable', 'string', 'max:255'],
            'from'       => ['nullable', 'date'],
            'to'         => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        if (! empty($filters['factory_id'])) {
            $this->authorizeFactoryAccess($request, (int) $filters['factory_id']);
        }

        $factoryId = $filters['factory_id'] ?? $request->user()?->factory_id;
        $report = null;

        if (! empty($filters['workshop']) && ! empty($filters['from']) && ! empty($filters['to'])) {
            $report = $this->postingReport->build($request, $filters);
        }

        return view('admin.tms.maintenance.posting', [
            'factories'  => $this->factoryOptions($request),
            'workshops'  => $this->maintenanceService->workshopOptions($factoryId ? (int) $factoryId : null),
            'filters'    => $filters,
            'report'     => $report,
        ]);
    }

    public function print(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $report = $this->postingReport->build($request, $filters);

        return view('admin.tms.maintenance.posting-print', compact('report'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $report = $this->postingReport->build($request, $filters);
        $filename = 'bill-for-posting-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [$report['factory_name']]);
            fputcsv($out, ['Bill For Posting', $report['workshop']]);
            fputcsv($out, ['Date', $report['report_date']]);
            fputcsv($out, []);
            fputcsv($out, ['SL', 'Car No', 'User', 'Description', 'Amount']);

            foreach ($report['groups'] as $group) {
                $first = true;
                foreach ($group['rows'] as $row) {
                    fputcsv($out, [
                        $first ? $group['sl'] : '',
                        $first ? $group['car_no'] : '',
                        $first ? $group['user'] : '',
                        $row['description'],
                        number_format($row['amount'], 2, '.', ''),
                    ]);
                    $first = false;
                }
            }

            fputcsv($out, []);
            fputcsv($out, ['', '', '', 'Grand Total', number_format($report['grand_total'], 2, '.', '')]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** @return array<string, mixed> */
    private function validatedFilters(Request $request): array
    {
        $filters = $request->validate([
            'factory_id' => ['nullable', 'exists:factories,id'],
            'workshop'   => ['required', 'string', 'max:255'],
            'from'       => ['required', 'date'],
            'to'         => ['required', 'date', 'after_or_equal:from'],
        ]);

        if (! empty($filters['factory_id'])) {
            $this->authorizeFactoryAccess($request, (int) $filters['factory_id']);
        }

        return $filters;
    }
}
