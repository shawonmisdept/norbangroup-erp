<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Jobs\Hrm\SyncBiometricDeviceJob;
use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\BiometricDevice;
use App\Models\Hrm\BiometricSyncLog;
use App\Services\Hrm\AttendanceDailyLogPhotoService;
use App\Services\Hrm\AttendanceProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function sync(Request $request)
    {
        $deviceQuery = BiometricDevice::query()
            ->with('factory')
            ->where('is_active', true)
            ->orderBy('name');

        $this->scopeDevicesToUserFactory($deviceQuery, $request);

        $devices = $deviceQuery->get();

        $punchQuery = AttendanceRawPunch::query()->with(['employee', 'biometricDevice']);
        $this->scopePunchesToUserFactory($punchQuery, $request);

        $stats = [
            'devices'        => $devices->count(),
            'punches_today'  => (clone $punchQuery)->whereDate('punched_at', today())->count(),
            'unmapped_today' => (clone $punchQuery)->whereDate('punched_at', today())->whereNull('employee_id')->count(),
            'unprocessed'    => (clone $punchQuery)->whereNull('processed_at')->whereNotNull('employee_id')->count(),
            'logs_today'     => $this->scopedDailyLogQuery($request)->whereDate('attendance_date', today())->count(),
        ];

        $recentPunches = (clone $punchQuery)->latest('punched_at')->limit(10)->get();

        $syncLogs = BiometricSyncLog::query()
            ->with('biometricDevice.factory')
            ->when($request->user()?->factory_id, function ($query) use ($request) {
                $query->whereHas(
                    'biometricDevice',
                    fn ($deviceQuery) => $deviceQuery->where('factory_id', $request->user()->factory_id)
                );
            })
            ->latest('started_at')
            ->limit(10)
            ->get();

        return view('admin.hrm.attendance.sync.index', compact('devices', 'stats', 'recentPunches', 'syncLogs'));
    }

    public function showPeriod(Request $request, AttendancePeriod $period)
    {
        $this->authorizePeriodAccess($request, $period);
        $period->load(['factory', 'processedByUser']);

        $query = DB::table('hrm_attendance_daily_logs as l')
            ->join('hrm_employees as e', 'e.id', '=', 'l.employee_id')
            ->where('l.attendance_period_id', $period->id)
            ->select([
                'l.employee_id',
                'e.name',
                'e.employee_code',
                'e.late_acceptance_enabled',
                DB::raw("SUM(CASE WHEN l.status IN ('present','late') THEN 1 ELSE 0 END) as present_count"),
                DB::raw("SUM(CASE WHEN l.status = 'late' THEN 1 ELSE 0 END) as late_count"),
                DB::raw('SUM(CASE WHEN l.status = \'late\' AND l.is_late_forgiven = 1 THEN 1 ELSE 0 END) as forgiven_count'),
                DB::raw("SUM(CASE WHEN l.status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                DB::raw("SUM(CASE WHEN l.status = 'leave' THEN 1 ELSE 0 END) as leave_count"),
            ])
            ->groupBy('l.employee_id', 'e.name', 'e.employee_code', 'e.late_acceptance_enabled')
            ->orderBy('e.employee_code');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('e.name', 'like', "%{$search}%")
                    ->orWhere('e.employee_code', 'like', "%{$search}%");
            });
        }

        $summaries = $query->paginate(25)->withQueryString();

        return view('admin.hrm.attendance.periods.show', [
            'period'    => $period,
            'summaries' => $summaries,
            'filters'   => $request->only(['search']),
        ]);
    }

    public function punches(Request $request)
    {
        $query = AttendanceRawPunch::query()
            ->with(['employee', 'biometricDevice', 'factory'])
            ->latest('punched_at');

        $this->scopePunchesToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('device_id')) {
            $query->where('biometric_device_id', $request->device_id);
        }

        if ($request->filled('mapped')) {
            $request->mapped === 'yes'
                ? $query->whereNotNull('employee_id')
                : $query->whereNull('employee_id');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('punched_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('punched_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('biometric_user_id', 'like', "%{$search}%")
                    ->orWhereHas('employee', fn ($eq) => $eq->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%"));
            });
        }

        $punches = $query->paginate(25)->withQueryString();

        $factories = $this->factoryOptions($request);
        $devices = BiometricDevice::query()
            ->when($request->user()?->factory_id, fn ($q) => $q->where('factory_id', $request->user()->factory_id))
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('admin.hrm.attendance.punches', [
            'punches'   => $punches,
            'factories' => $factories,
            'devices'   => $devices,
            'filters'   => $request->only(['search', 'factory_id', 'device_id', 'mapped', 'date_from', 'date_to']),
        ]);
    }

    public function syncDevice(Request $request, BiometricDevice $device)
    {
        $this->authorizeDeviceAccess($request, $device);

        SyncBiometricDeviceJob::dispatch($device->id);

        return redirect()->back()->with(
            'success',
            "Sync queued for {$device->name}. Refresh this page in a moment to see updated status."
        );
    }

    public function syncAll(Request $request)
    {
        $query = BiometricDevice::query()
            ->where('is_active', true)
            ->whereNotNull('adms_url')
            ->where('adms_url', '!=', '');

        $this->scopeDevicesToUserFactory($query, $request);

        $devices = $query->get();

        if ($devices->isEmpty()) {
            return redirect()->back()->with('error', 'No active devices with ADMS URL configured.');
        }

        foreach ($devices as $device) {
            SyncBiometricDeviceJob::dispatch($device->id);
        }

        return redirect()->back()->with('success', "Queued sync for {$devices->count()} device(s). Refresh shortly for results.");
    }

    public function syncFailures(Request $request)
    {
        $staleMinutes = config('hrm.sync.stale_push_minutes', 60);
        $staleCutoff = now()->subMinutes($staleMinutes);

        $deviceQuery = BiometricDevice::query()
            ->with('factory')
            ->where('is_active', true)
            ->orderBy('name');

        $this->scopeDevicesToUserFactory($deviceQuery, $request);

        $devices = $deviceQuery->get();

        $failedDevices = $devices->filter(fn ($d) => $d->last_sync_status === 'failed');

        $staleDevices = $devices->filter(function ($device) use ($staleCutoff) {
            if ($device->last_sync_status === 'failed') {
                return false;
            }

            if ($device->last_seen_at) {
                return $device->last_seen_at->lt($staleCutoff);
            }

            if ($device->hasAdmsEndpoint() && $device->last_synced_at) {
                return $device->last_synced_at->lt($staleCutoff);
            }

            return $device->hasAdmsEndpoint() && ! $device->last_synced_at && $device->created_at->lt($staleCutoff);
        });

        $failedLogs = BiometricSyncLog::query()
            ->with('biometricDevice.factory')
            ->where('status', 'failed')
            ->when($request->user()?->factory_id, function ($query) use ($request) {
                $query->whereHas(
                    'biometricDevice',
                    fn ($deviceQuery) => $deviceQuery->where('factory_id', $request->user()->factory_id)
                );
            })
            ->latest('started_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.hrm.attendance.sync.failures', [
            'failedDevices' => $failedDevices,
            'staleDevices'  => $staleDevices,
            'failedLogs'    => $failedLogs,
            'staleMinutes'  => $staleMinutes,
            'stats'         => [
                'failed' => $failedDevices->count(),
                'stale'  => $staleDevices->count(),
                'total'  => $devices->count(),
            ],
        ]);
    }

    public function daily(Request $request, AttendanceDailyLogPhotoService $photoService)
    {
        $query = AttendanceDailyLog::query()
            ->with(['employee', 'shift', 'period', 'lateAcceptanceApplication'])
            ->latest('attendance_date')
            ->latest('id');

        $this->scopeDailyLogsToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('attendance_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('attendance_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%"));
        }

        $logs = $query->paginate(25)->withQueryString();
        $photoService->attachMobileCheckInPhotos($logs);

        return view('admin.hrm.attendance.daily', [
            'logs'      => $logs,
            'factories' => $this->factoryOptions($request),
            'statuses'  => AttendanceDailyLog::STATUSES,
            'filters'   => $request->only(['search', 'factory_id', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function periods(Request $request)
    {
        $query = AttendancePeriod::query()
            ->with(['factory', 'processedByUser'])
            ->latest('year')
            ->latest('month');

        if ($request->user()?->factory_id) {
            $query->where('factory_id', $request->user()->factory_id);
        }

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $periods = $query->paginate(20)->withQueryString();

        return view('admin.hrm.attendance.periods', [
            'periods'   => $periods,
            'factories' => $this->factoryOptions($request),
            'statuses'  => AttendancePeriod::STATUSES,
            'filters'   => $request->only(['factory_id', 'status']),
        ]);
    }

    public function process(Request $request, AttendanceProcessor $processor)
    {
        $validated = $request->validate([
            'factory_id'    => ['required', 'exists:factories,id'],
            'year'          => ['required', 'integer', 'min:2020', 'max:2100'],
            'month'         => ['required', 'integer', 'min:1', 'max:12'],
            'mark_absences' => ['nullable', 'boolean'],
        ]);

        if ($request->user()?->factory_id && (int) $validated['factory_id'] !== $request->user()->factory_id) {
            abort(403);
        }

        $period = AttendancePeriod::getOrCreateForMonth(
            (int) $validated['factory_id'],
            (int) $validated['year'],
            (int) $validated['month']
        );

        if ($period->isFrozen()) {
            return redirect()->back()->with('error', 'This period is frozen and cannot be reprocessed.');
        }

        $result = $processor->processPeriod(
            $period,
            $request->user()?->id,
            $request->boolean('mark_absences', true)
        );

        return redirect()->back()->with('success', $result['message']);
    }

    public function processToday(Request $request, AttendanceProcessor $processor)
    {
        $validated = $request->validate([
            'factory_id'    => ['nullable', 'exists:factories,id'],
            'mark_absences' => ['nullable', 'boolean'],
        ]);

        $factoryIds = $validated['factory_id']
            ? [(int) $validated['factory_id']]
            : array_keys($this->factoryOptions($request));

        if ($factoryIds === []) {
            return redirect()->back()->with('error', 'No factory available to process.');
        }

        $date = now()->startOfDay();
        $total = 0;
        $absences = 0;

        foreach ($factoryIds as $factoryId) {
            if ($request->user()?->factory_id && $request->user()->factory_id !== $factoryId) {
                continue;
            }

            $period = AttendancePeriod::getOrCreateForMonth($factoryId, $date->year, $date->month);
            $total += $processor->processDate($factoryId, $date, $period);

            if ($request->boolean('mark_absences', false)) {
                $absences += $processor->markAbsences($factoryId, $date, $date, $period);
            }
        }

        return redirect()->back()->with('success', "Processed {$total} employee-day(s) for today. Absences marked: {$absences}.");
    }

    public function freezePeriod(Request $request, AttendancePeriod $period, AttendanceProcessor $processor)
    {
        $this->authorizePeriodAccess($request, $period);

        if ($period->isFrozen()) {
            return redirect()->back()->with('error', 'Period is already frozen.');
        }

        $processor->freezePeriod($period);

        return redirect()->back()->with('success', $period->periodLabel() . ' has been frozen.');
    }

    private function scopedDailyLogQuery(Request $request)
    {
        $query = AttendanceDailyLog::query();
        $this->scopeDailyLogsToUserFactory($query, $request);

        return $query;
    }

    private function scopeDailyLogsToUserFactory($query, Request $request): void
    {
        if ($request->user()?->factory_id) {
            $query->where('factory_id', $request->user()->factory_id);
        }
    }

    private function authorizePeriodAccess(Request $request, AttendancePeriod $period): void
    {
        if ($request->user()?->factory_id && $request->user()->factory_id !== $period->factory_id) {
            abort(403);
        }
    }

    private function factoryOptions(Request $request): array
    {
        $query = Factory::where('is_active', true)->orderBy('name');

        if ($request->user()?->factory_id) {
            $query->where('id', $request->user()->factory_id);
        }

        return $query->pluck('name', 'id')->all();
    }

    private function scopeDevicesToUserFactory($query, Request $request): void
    {
        if ($request->user()?->factory_id) {
            $query->where('factory_id', $request->user()->factory_id);
        }
    }

    private function scopePunchesToUserFactory($query, Request $request): void
    {
        if ($request->user()?->factory_id) {
            $query->where('factory_id', $request->user()->factory_id);
        }
    }

    private function authorizeDeviceAccess(Request $request, BiometricDevice $device): void
    {
        if ($request->user()?->factory_id && $request->user()->factory_id !== $device->factory_id) {
            abort(403);
        }
    }
}
