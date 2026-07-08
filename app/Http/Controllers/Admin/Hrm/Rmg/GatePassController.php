<?php

namespace App\Http\Controllers\Admin\Hrm\Rmg;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\GatePass;
use App\Services\Hrm\HrmNotificationService;
use App\Support\TimeInput;
use Illuminate\Http\Request;

class GatePassController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = GatePass::query()->with('employee')->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('admin.hrm.rmg.gate-pass.index', [
            'passes'    => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id', 'status']),
            'statuses'  => GatePass::STATUSES,
            'canManage' => $request->user()?->canManageRmgSubmodule('gate-pass') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.rmg.gate-pass.form', [
            'pass'      => new GatePass(['status' => 'pending', 'pass_date' => now()->toDateString()]),
            'factories' => $this->factoryOptions($request),
            'employees' => $this->employeeOptions($request),
        ]);
    }

    public function store(Request $request, HrmNotificationService $notifier)
    {
        $validated = $this->validatePass($request);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        abort_if($employee->factory_id !== (int) $validated['factory_id'], 422);

        $pass = GatePass::create($validated + [
            'status'     => 'pending',
            'created_by' => $request->user()->id,
        ]);

        $notifier->gatePassPending($pass->fresh(['employee']));

        return redirect()->route('admin.hrm.rmg.gate-pass.index')
            ->with('success', 'Gate pass submitted for approval.');
    }

    public function edit(Request $request, GatePass $gatePass)
    {
        $this->authorizeFactoryAccess($request, $gatePass->factory_id);

        if (! $this->canModify($gatePass)) {
            return redirect()->route('admin.hrm.rmg.gate-pass.index')
                ->with('error', 'Only pending gate passes can be edited.');
        }

        return view('admin.hrm.rmg.gate-pass.form', [
            'pass'      => $gatePass,
            'factories' => $this->factoryOptions($request),
            'employees' => $this->employeeOptions($request, $gatePass->employee_id),
        ]);
    }

    public function update(Request $request, GatePass $gatePass)
    {
        $this->authorizeFactoryAccess($request, $gatePass->factory_id);

        if (! $this->canModify($gatePass)) {
            return back()->with('error', 'Only pending gate passes can be edited.');
        }

        $validated = $this->validatePass($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $employee = Employee::findOrFail($validated['employee_id']);
        abort_if($employee->factory_id !== (int) $validated['factory_id'], 422);

        $gatePass->update($validated);

        return redirect()->route('admin.hrm.rmg.gate-pass.index')
            ->with('success', 'Gate pass updated.');
    }

    public function destroy(Request $request, GatePass $gatePass)
    {
        $this->authorizeFactoryAccess($request, $gatePass->factory_id);

        if (! $this->canDelete($gatePass)) {
            return back()->with('error', 'This gate pass cannot be deleted.');
        }

        $gatePass->delete();

        return redirect()->route('admin.hrm.rmg.gate-pass.index')
            ->with('success', 'Gate pass deleted.');
    }

    public function approve(Request $request, GatePass $gatePass)
    {
        $this->authorizeFactoryAccess($request, $gatePass->factory_id);

        if ($gatePass->status !== 'pending') {
            return back()->with('error', 'Only pending gate passes can be approved.');
        }

        $gatePass->update([
            'status'      => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return redirect()->route('admin.hrm.rmg.gate-pass.index')
            ->with('success', 'Gate pass approved.');
    }

    public function reject(Request $request, GatePass $gatePass)
    {
        $this->authorizeFactoryAccess($request, $gatePass->factory_id);

        if ($gatePass->status !== 'pending') {
            return back()->with('error', 'Only pending gate passes can be rejected.');
        }

        $gatePass->update([
            'status'      => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return redirect()->route('admin.hrm.rmg.gate-pass.index')
            ->with('success', 'Gate pass rejected.');
    }

    private function employeeOptions(Request $request, ?int $includeId = null): array
    {
        $query = Employee::query()
            ->orderBy('name');

        if ($includeId) {
            $query->where(function ($q) use ($includeId) {
                $q->whereIn('status', ['active', 'probation'])
                    ->orWhere('id', $includeId);
            });
        } else {
            $query->whereIn('status', ['active', 'probation']);
        }

        $this->scopeToUserFactory($query, $request);

        return $query->pluck('name', 'id')->all();
    }

    /** @return array<string, mixed> */
    private function validatePass(Request $request): array
    {
        $validated = $request->validate([
            'factory_id'       => ['required', 'exists:factories,id'],
            'employee_id'      => ['required', 'exists:hrm_employees,id'],
            'pass_date'        => ['required', 'date'],
            'out_time'         => ['nullable', 'date_format:H:i'],
            'expected_in_time' => ['nullable', 'date_format:H:i'],
            'destination'      => ['nullable', 'string', 'max:255'],
            'reason'           => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['out_time'] = TimeInput::normalize($validated['out_time'] ?? null);
        $validated['expected_in_time'] = TimeInput::normalize($validated['expected_in_time'] ?? null);

        return $validated;
    }

    private function canModify(GatePass $gatePass): bool
    {
        return $gatePass->status === 'pending';
    }

    private function canDelete(GatePass $gatePass): bool
    {
        return in_array($gatePass->status, ['pending', 'rejected'], true);
    }
}
