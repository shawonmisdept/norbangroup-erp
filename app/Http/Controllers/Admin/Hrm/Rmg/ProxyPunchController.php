<?php

namespace App\Http\Controllers\Admin\Hrm\Rmg;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\Employee;
use App\Models\Hrm\ProxyPunchFlag;
use App\Services\Hrm\HrmNotificationService;
use App\Support\PortalDateTime;
use Illuminate\Http\Request;

class ProxyPunchController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = ProxyPunchFlag::query()->with(['employee', 'punch'])->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('admin.hrm.rmg.proxy-punch.index', [
            'flags'     => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id', 'status']),
            'canManage' => $request->user()?->canManageRmgSubmodule('proxy-punch') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.rmg.proxy-punch.form', [
            'flag'      => new ProxyPunchFlag(['status' => 'open']),
            'factories' => $this->factoryOptions($request),
            'employees' => $this->employeeOptions($request),
            'punches'   => $this->recentPunchOptions($request),
        ]);
    }

    public function store(Request $request, HrmNotificationService $notifier)
    {
        $validated = $request->validate([
            'factory_id'               => ['required', 'exists:factories,id'],
            'attendance_raw_punch_id'  => ['required', 'exists:hrm_attendance_raw_punches,id'],
            'employee_id'              => ['nullable', 'exists:hrm_employees,id'],
            'reason'                   => ['nullable', 'string', 'max:1000'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $punch = AttendanceRawPunch::findOrFail($validated['attendance_raw_punch_id']);
        abort_if($punch->factory_id !== (int) $validated['factory_id'], 422);

        $flag = ProxyPunchFlag::create($validated + [
            'status'     => 'open',
            'flagged_by' => $request->user()->id,
        ]);

        $notifier->proxyPunchFlagged($flag->fresh(['employee', 'punch']));

        return redirect()->route('admin.hrm.rmg.proxy-punch.index')
            ->with('success', 'Proxy punch flag recorded.');
    }

    public function review(Request $request, ProxyPunchFlag $proxyPunchFlag)
    {
        $this->authorizeFactoryAccess($request, $proxyPunchFlag->factory_id);

        $validated = $request->validate([
            'status' => ['required', 'in:reviewed,dismissed,confirmed'],
        ]);

        if ($proxyPunchFlag->status !== 'open') {
            return back()->with('error', 'Only open flags can be reviewed.');
        }

        $proxyPunchFlag->update([
            'status'      => $validated['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.hrm.rmg.proxy-punch.index')
            ->with('success', 'Proxy punch flag updated.');
    }

    private function employeeOptions(Request $request): array
    {
        $query = Employee::query()
            ->whereIn('status', ['active', 'probation'])
            ->orderBy('name');

        $this->scopeToUserFactory($query, $request);

        return $query->pluck('name', 'id')->all();
    }

    private function recentPunchOptions(Request $request): array
    {
        $query = AttendanceRawPunch::query()
            ->latest('id')
            ->limit(100);

        $this->scopeToUserFactory($query, $request);

        return $query->get()->mapWithKeys(function (AttendanceRawPunch $punch) {
            $label = $punch->id . ' — ' . PortalDateTime::dateTimeShort($punch->punch_time);

            return [$punch->id => $label];
        })->all();
    }
}
