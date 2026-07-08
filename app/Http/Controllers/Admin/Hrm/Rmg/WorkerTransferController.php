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
            'statuses'  => WorkerTransfer::STATUSES,
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
        $validated = $this->validateTransfer($request);

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

    public function edit(Request $request, WorkerTransfer $workerTransfer)
    {
        $this->authorizeFactoryAccess($request, $workerTransfer->factory_id);

        if (! $this->canModify($workerTransfer)) {
            return redirect()->route('admin.hrm.rmg.worker-transfer.index')
                ->with('error', 'Only pending transfers can be edited.');
        }

        return view('admin.hrm.rmg.worker-transfer.form', [
            'transfer'  => $workerTransfer,
            'factories' => $this->factoryOptions($request),
            'employees' => $this->employeeOptions($request, $workerTransfer->employee_id),
            'lines'     => $this->lineOptions($request),
            'floors'    => $this->floorOptions($request),
            'buildings' => $this->buildingOptions($request),
        ]);
    }

    public function update(Request $request, WorkerTransfer $workerTransfer)
    {
        $this->authorizeFactoryAccess($request, $workerTransfer->factory_id);

        if (! $this->canModify($workerTransfer)) {
            return back()->with('error', 'Only pending transfers can be edited.');
        }

        $validated = $this->validateTransfer($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $employee = Employee::findOrFail($validated['employee_id']);
        abort_if($employee->factory_id !== (int) $validated['factory_id'], 422);

        $workerTransfer->update($validated);

        return redirect()->route('admin.hrm.rmg.worker-transfer.index')
            ->with('success', 'Transfer request updated.');
    }

    public function destroy(Request $request, WorkerTransfer $workerTransfer)
    {
        $this->authorizeFactoryAccess($request, $workerTransfer->factory_id);

        if (! $this->canDelete($workerTransfer)) {
            return back()->with('error', 'This transfer cannot be deleted.');
        }

        $workerTransfer->delete();

        return redirect()->route('admin.hrm.rmg.worker-transfer.index')
            ->with('success', 'Transfer request deleted.');
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

    public function reject(Request $request, WorkerTransfer $workerTransfer)
    {
        $this->authorizeFactoryAccess($request, $workerTransfer->factory_id);

        if ($workerTransfer->status !== 'pending') {
            return back()->with('error', 'Only pending transfers can be rejected.');
        }

        $workerTransfer->update([
            'status'      => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return redirect()->route('admin.hrm.rmg.worker-transfer.index')
            ->with('success', 'Transfer request rejected.');
    }

    private function employeeOptions(Request $request, ?int $includeId = null): array
    {
        $query = Employee::query()->orderBy('name');

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

    /** @return array<string, mixed> */
    private function validateTransfer(Request $request): array
    {
        return $request->validate([
            'factory_id'     => ['required', 'exists:factories,id'],
            'employee_id'    => ['required', 'exists:hrm_employees,id'],
            'to_factory_id'  => ['nullable', 'exists:factories,id'],
            'to_line_id'     => ['nullable', 'exists:hrm_lines,id'],
            'to_floor_id'    => ['nullable', 'exists:hrm_floors,id'],
            'to_building_id' => ['nullable', 'exists:hrm_buildings,id'],
            'effective_date' => ['required', 'date'],
            'reason'         => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function canModify(WorkerTransfer $transfer): bool
    {
        return $transfer->status === 'pending';
    }

    private function canDelete(WorkerTransfer $transfer): bool
    {
        return in_array($transfer->status, ['pending', 'rejected'], true);
    }
}
