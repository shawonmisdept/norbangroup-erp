<?php

namespace App\Http\Controllers\Admin\Hrm\Compliance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Services\Hrm\StatutoryRegisterService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgeVerificationController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request, StatutoryRegisterService $registers)
    {
        $factories = $this->factoryOptions($request);
        $factoryId = (int) ($request->factory_id ?? array_key_first($factories) ?? 0);

        if ($factoryId && $request->user()?->factory_id) {
            $this->authorizeFactoryAccess($request, $factoryId);
        }

        $rows = $factoryId ? $registers->ageVerificationReport($factoryId) : [];
        $nonCompliant = collect($rows)->where('compliant', 'No')->count();

        return view('admin.hrm.compliance.age-verification.index', [
            'factories'    => $factories,
            'factoryId'    => $factoryId,
            'rows'         => $rows,
            'nonCompliant' => $nonCompliant,
            'filters'      => $request->only(['factory_id']),
        ]);
    }

    public function export(Request $request, StatutoryRegisterService $registers): StreamedResponse
    {
        $factoryId = (int) $request->input('factory_id');
        $this->authorizeFactoryAccess($request, $factoryId);

        $rows = $registers->ageVerificationReport($factoryId);
        $filename = sprintf('age-verification-%d.csv', $factoryId);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee Code', 'Name', 'Date of Birth', 'Age', 'Joining Date', 'Compliant', 'Min Age']);

            foreach ($rows as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
