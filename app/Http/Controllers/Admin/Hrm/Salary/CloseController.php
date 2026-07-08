<?php

namespace App\Http\Controllers\Admin\Hrm\Salary;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Jobs\Hrm\SendPeriodPayslipsJob;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Services\Hrm\PayrollProcessor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CloseController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = PayrollPeriod::query()
            ->with(['factory', 'frozenByUser'])
            ->withCount('items')
            ->whereIn('status', ['calculated', 'frozen'])
            ->latest('year')
            ->latest('month');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $periods = $query->paginate(20)->withQueryString();

        return view('admin.hrm.salary.close.index', [
            'periods'   => $periods,
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id', 'status']),
        ]);
    }

    public function freeze(Request $request, PayrollPeriod $period, PayrollProcessor $processor)
    {
        $this->authorizeFactoryAccess($request, $period->factory_id);

        try {
            $processor->freezePeriod($period, $request->user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        if ($request->boolean('send_payslips', true)) {
            SendPeriodPayslipsJob::dispatch($period->fresh()->id);
        }

        $message = $period->periodLabel() . ' salary closed.';

        if ($request->boolean('send_payslips', true)) {
            $message .= ' Payslip emails queued.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function sendPayslips(Request $request, PayrollPeriod $period)
    {
        $this->authorizeFactoryAccess($request, $period->factory_id);

        if (! $period->isFrozen()) {
            return redirect()->back()->withErrors(['period' => 'Payslips can only be sent for closed periods.']);
        }

        SendPeriodPayslipsJob::dispatch($period->id);

        return redirect()->back()->with('success', 'Payslip emails queued for ' . $period->periodLabel() . '.');
    }

    public function bankAdvise(Request $request, PayrollPeriod $period): StreamedResponse
    {
        $this->authorizeFactoryAccess($request, $period->factory_id);

        if (! $period->isFrozen()) {
            abort(422, 'Bank advise is available only for closed periods.');
        }

        $filename = sprintf('bank-advise-%s-%s.csv', $period->factory_id, $period->periodLabel());

        return response()->streamDownload(function () use ($period) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee Code', 'Employee Name', 'Bank', 'Account Number', 'Bank Pay', 'Payment Method']);

            PayrollItem::query()
                ->with(['employee', 'salaryBank'])
                ->where('payroll_period_id', $period->id)
                ->where('bank_pay_amount', '>', 0)
                ->orderBy('employee_id')
                ->chunk(100, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        fputcsv($handle, [
                            $item->employee->employee_code,
                            $item->employee->name,
                            $item->salaryBank?->displayName() ?? '',
                            $item->bank_account ?? '',
                            number_format((float) $item->bank_pay_amount, 2, '.', ''),
                            $item->payment_method,
                        ]);
                    }
                });

            fclose($handle);
        }, str_replace(' ', '-', strtolower($filename)), ['Content-Type' => 'text/csv']);
    }
}
