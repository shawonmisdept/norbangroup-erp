<?php

namespace App\Http\Controllers\Admin\Hrm\Rmg;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Building;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Floor;
use App\Models\Hrm\Line;
use App\Models\Hrm\WorkerTransfer;
use App\Services\Hrm\HrmNotificationService;
use App\Services\Hrm\WorkerTransferService;
use Illuminate\Http\Request;

class WorkerTransferController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = WorkerTransfer::query()->with(['employee', 'toLine', 'toFactory'])->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('admin.hrm.rmg.worker-transfer.index', [
            'transfers' => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id', 'status']),
            'canManage' => $request->user()?->canManageRmgSubmodule('worker-transfer') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.rmg.worker-transfer.form', [
            'transfer'  => new WorkerTransfer(['status' => 'pending']),
            'factories' => $this->factoryOptions($request),
            'employees' => $this->employeeOptions($request),
            'lines'     => $this->lineOptions($request),
            'floors'    => $this->floorOptions($request),
            'buildings' => $this->buildingOptions($request),
        ]);
    }

    public function store(Request $request, HrmNotificationService $notifier)
    {
        $validated = $request->validate([
            'factory_id'      => ['required', 'exists:factories,id'],
            'employee_id'     => ['required', 'exists:hrm_employees,id'],
            'to_factory_id'   => ['nullable', 'exists:factories,id'],
            'to_line_id'      => ['nullable', 'exists:hrm_lines,id'],
            'to_floor_id'     => ['nullable', 'exists:hrm_floors,id'],
            'to_building_id'  => ['nullable', 'exists:hrm_buildings,id'],
            'effective_date'  => ['required', 'date'],
            'reason'          => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        abort_if($employee->factory_id !== (int) $validated['factory_id'], 422);

        $transfer = WorkerTransfer::create($validated + [
            'status'     => 'pending',
            'created_by' => $request->user()->id,
        ]);

        $notifier->workerTransferPending($transfer->fresh(['employee']));

        return redirect()->route('admin.hrm.rmg.worker-transfer.index')
            ->with('success', 'Worker transfer submitted for approval.');
    }

    public function approve(
        Request $request,
        WorkerTransfer $workerTransfer,
        WorkerTransferService $service
    ) {
        $this->authorizeFactoryAccess($request, $workerTransfer->factory_id);

        if ($workerTransfer->status !== 'pending') {
            return back()->with('error', 'Only pending transfers can be approved.');
        }

        $service->approve($workerTransfer, $request->user()->id);

        return redirect()->route('admin.hrm.rmg.worker-transfer.index')
            ->with('success', 'Transfer approved and employee assignment updated.');
    }

    private function employeeOptions(Request $request): array
    {
        $query = Employee::query()
            ->whereIn('status', ['active', 'probation'])
            ->orderBy('name');

        $this->scopeToUserFactory($query, $request);

        return $query->pluck('name', 'id')->all();
    }

    private function lineOptions(Request $request): array
    {
        $query = Line::query()->where('is_active', true)->orderBy('name');
        $this->scopeToUserFactory($query, $request);

        return $query->pluck('name', 'id')->all();
    }

    private function floorOptions(Request $request): array
    {
        $query = Floor::query()->where('is_active', true)->orderBy('name');
        $this->scopeToUserFactory($query, $request);

        return $query->pluck('name', 'id')->all();
    }

    private function buildingOptions(Request $request): array
    {
        $query = Building::query()->where('is_active', true)->orderBy('name');
        $this->scopeToUserFactory($query, $request);

        return $query->pluck('name', 'id')->all();
    }
}
