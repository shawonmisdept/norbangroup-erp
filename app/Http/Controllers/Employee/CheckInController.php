<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendanceGatePoint;
use App\Services\Hrm\AttendancePunchService;
use App\Services\Hrm\EmployeeCheckInStatusService;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function create(Request $request, EmployeeCheckInStatusService $statusService)
    {
        $employee = $request->user('employee')->employee->load('factory', 'shift');

        $gate = null;
        if ($request->filled('gate')) {
            $gate = AttendanceGatePoint::query()
                ->where('qr_token', $request->gate)
                ->where('factory_id', $employee->factory_id)
                ->where('is_active', true)
                ->first();
        }

        $checkInStatus = $statusService->forEmployee($employee);
        $nextAction = $checkInStatus['next_action'] === 'done'
            ? 'out'
            : ($checkInStatus['next_action'] ?? 'in');

        $todayLog = AttendanceDailyLog::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->first();

        return view('employee.attendance.check-in', compact(
            'employee',
            'gate',
            'checkInStatus',
            'nextAction',
            'todayLog',
        ));
    }

    public function store(Request $request, AttendancePunchService $punchService)
    {
        $employee = $request->user('employee')->employee->load('factory');

        $validated = $request->validate([
            'punch_type' => ['required', 'in:in,out'],
            'latitude'   => ['required', 'numeric', 'between:-90,90'],
            'longitude'  => ['required', 'numeric', 'between:-180,180'],
            'photo'      => ['nullable', 'string'],
            'gate'       => ['nullable', 'string', 'max:64'],
        ]);

        $gatePoint = null;
        if (! empty($validated['gate'])) {
            $gatePoint = AttendanceGatePoint::query()
                ->where('qr_token', $validated['gate'])
                ->where('factory_id', $employee->factory_id)
                ->where('is_active', true)
                ->first();

            if (! $gatePoint) {
                return redirect()
                    ->route('employee.attendance.check-in', array_filter(['gate' => $validated['gate'] ?? null]))
                    ->withErrors(['gate' => 'Invalid or inactive gate QR code.'])
                    ->withInput($request->except('photo'));
            }
        }

        try {
            $punchService->recordMobile($employee, $validated['punch_type'], [
                'latitude'   => (float) $validated['latitude'],
                'longitude'  => (float) $validated['longitude'],
                'photo'      => $validated['photo'] ?? null,
                'gate_point' => $gatePoint,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('employee.attendance.check-in', array_filter(['gate' => $validated['gate'] ?? null]))
                ->withErrors([
                    'check_in' => 'Could not save check-in right now. Please try again or contact HR.',
                ])
                ->withInput($request->except('photo'));
        }

        $label = $validated['punch_type'] === 'in' ? 'Check-in' : 'Check-out';

        return redirect()
            ->route('employee.dashboard')
            ->with('success', "{$label} recorded successfully.");
    }
}
