<?php

namespace App\Http\Controllers\Admin\Hrm\Compliance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\GratuitySettlement;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GratuityController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = GratuitySettlement::query()
            ->with(['employee', 'factory'])
            ->latest('separation_date')
            ->latest('id');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $factories = $this->factoryOptions($request);
        $factoryId = (int) ($request->factory_id ?? array_key_first($factories) ?? 0);

        return view('admin.hrm.compliance.gratuity.index', [
            'settlements' => $query->paginate(25)->withQueryString(),
            'factories'   => $factories,
            'factoryId'   => $factoryId,
            'filters'     => $request->only(['factory_id', 'status']),
            'canManage'   => $request->user()?->canManageComplianceSubmodule('gratuity') ?? false,
        ]);
    }

    public function show(Request $request, GratuitySettlement $gratuitySettlement)
    {
        $this->authorizeFactoryAccess($request, $gratuitySettlement->factory_id);
        $gratuitySettlement->load(['employee', 'factory', 'calculator']);

        return view('admin.hrm.compliance.gratuity.show', [
            'settlement' => $gratuitySettlement,
            'canManage'  => $request->user()?->canManageComplianceSubmodule('gratuity') ?? false,
        ]);
    }

    public function markPaid(Request $request, GratuitySettlement $gratuitySettlement)
    {
        $this->authorizeFactoryAccess($request, $gratuitySettlement->factory_id);

        $gratuitySettlement->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        return redirect()->route('admin.hrm.compliance.gratuity.show', $gratuitySettlement)
            ->with('success', 'Gratuity marked as paid.');
    }

    public function export(Request $request): StreamedResponse
    {
        $factoryId = (int) $request->input('factory_id');
        $this->authorizeFactoryAccess($request, $factoryId);

        $settlements = GratuitySettlement::query()
            ->where('factory_id', $factoryId)
            ->with('employee')
            ->orderByDesc('separation_date')
            ->get();

        $filename = sprintf('gratuity-register-%d.csv', $factoryId);

        return response()->streamDownload(function () use ($settlements) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee Code', 'Name', 'Separation Date', 'Years of Service', 'Last Basic', 'Gratuity Amount', 'Status']);

            foreach ($settlements as $row) {
                fputcsv($handle, [
                    $row->employee?->employee_code,
                    $row->employee?->name,
                    $row->separation_date->format('Y-m-d'),
                    $row->years_of_service,
                    $row->last_basic_salary,
                    $row->gratuity_amount,
                    $row->status,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
