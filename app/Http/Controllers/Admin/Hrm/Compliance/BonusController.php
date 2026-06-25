<?php

namespace App\Http\Controllers\Admin\Hrm\Compliance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\BonusRun;
use App\Services\Hrm\FestivalBonusCalculator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BonusController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = BonusRun::query()->with(['factory', 'calculator'])->latest('year')->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return view('admin.hrm.compliance.bonus.index', [
            'runs'      => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
            'canManage' => $request->user()?->canManageComplianceSubmodule('bonus') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.compliance.bonus.form', [
            'run'       => new BonusRun(['year' => now()->year, 'bonus_type' => 'eid_ul_fitr', 'status' => 'draft']),
            'factories' => $this->factoryOptions($request),
            'types'     => BonusRun::BONUS_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'factory_id'  => ['required', 'exists:factories,id'],
            'bonus_type'  => ['required', 'in:' . implode(',', array_keys(BonusRun::BONUS_TYPES))],
            'year'        => ['required', 'integer', 'min:2020', 'max:2100'],
            'bonus_date'  => ['nullable', 'date'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        BonusRun::create($validated + ['status' => 'draft']);

        return redirect()->route('admin.hrm.compliance.bonus.index')->with('success', 'Bonus run created.');
    }

    public function show(Request $request, BonusRun $bonusRun)
    {
        $this->authorizeFactoryAccess($request, $bonusRun->factory_id);
        $bonusRun->load(['items.employee', 'factory']);

        return view('admin.hrm.compliance.bonus.show', [
            'run'       => $bonusRun,
            'canManage' => $request->user()?->canManageComplianceSubmodule('bonus') ?? false,
        ]);
    }

    public function calculate(Request $request, BonusRun $bonusRun, FestivalBonusCalculator $calculator)
    {
        $this->authorizeFactoryAccess($request, $bonusRun->factory_id);

        if ($bonusRun->status === 'approved') {
            return redirect()->route('admin.hrm.compliance.bonus.show', $bonusRun)
                ->with('error', 'Approved bonus runs cannot be recalculated.');
        }

        $calculator->calculate($bonusRun, $request->user());

        return redirect()->route('admin.hrm.compliance.bonus.show', $bonusRun)
            ->with('success', 'Bonus calculated for all eligible employees.');
    }

    public function approve(Request $request, BonusRun $bonusRun)
    {
        $this->authorizeFactoryAccess($request, $bonusRun->factory_id);

        if ($bonusRun->status !== 'calculated') {
            return redirect()->route('admin.hrm.compliance.bonus.show', $bonusRun)
                ->with('error', 'Only calculated bonus runs can be approved.');
        }

        if ($bonusRun->items()->doesntExist()) {
            return redirect()->route('admin.hrm.compliance.bonus.show', $bonusRun)
                ->with('error', 'Cannot approve a bonus run with no calculated items.');
        }

        $bonusRun->update(['status' => 'approved']);

        return redirect()->route('admin.hrm.compliance.bonus.show', $bonusRun)
            ->with('success', 'Bonus run approved. Export is now available for payroll.');
    }

    public function export(Request $request, BonusRun $bonusRun): StreamedResponse
    {
        $this->authorizeFactoryAccess($request, $bonusRun->factory_id);

        if ($bonusRun->status !== 'approved') {
            abort(403, 'Bonus run must be approved before export.');
        }

        $bonusRun->load('items.employee');

        $filename = sprintf('bonus-%s-%d.csv', $bonusRun->bonus_type, $bonusRun->year);

        return response()->streamDownload(function () use ($bonusRun) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee Code', 'Name', 'Basic Avg', 'Months Worked', 'Bonus Amount']);

            foreach ($bonusRun->items as $item) {
                fputcsv($handle, [
                    $item->employee?->employee_code,
                    $item->employee?->name,
                    $item->basic_avg,
                    $item->months_worked,
                    $item->bonus_amount,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
