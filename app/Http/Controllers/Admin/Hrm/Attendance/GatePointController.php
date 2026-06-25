<?php

namespace App\Http\Controllers\Admin\Hrm\Attendance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\AttendanceGatePoint;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GatePointController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = AttendanceGatePoint::query()->with('factory')->orderBy('name');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        $points = $query->paginate(20)->withQueryString();

        return view('admin.hrm.attendance.gate-points.index', [
            'points'    => $points,
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.attendance.gate-points.form', [
            'point'     => new AttendanceGatePoint(['is_active' => true]),
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePoint($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        $validated['qr_token'] = Str::random(32);

        AttendanceGatePoint::create($validated);

        return redirect()
            ->route('admin.hrm.attendance.gate-points.index')
            ->with('success', 'Gate point created. Print the QR code for the gate.');
    }

    public function edit(Request $request, AttendanceGatePoint $gatePoint)
    {
        $this->authorizePoint($request, $gatePoint);

        return view('admin.hrm.attendance.gate-points.form', [
            'point'     => $gatePoint,
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function update(Request $request, AttendanceGatePoint $gatePoint)
    {
        $this->authorizePoint($request, $gatePoint);
        $gatePoint->update($this->validatePoint($request, $gatePoint));

        return redirect()
            ->route('admin.hrm.attendance.gate-points.index')
            ->with('success', 'Gate point updated.');
    }

    public function qr(Request $request, AttendanceGatePoint $gatePoint)
    {
        $this->authorizePoint($request, $gatePoint);

        return view('admin.hrm.attendance.gate-points.qr', [
            'point'   => $gatePoint,
            'checkInUrl' => $gatePoint->checkInUrl(),
        ]);
    }

    private function validatePoint(Request $request): array
    {
        $validated = $request->validate([
            'factory_id' => ['required', 'exists:factories,id'],
            'code'       => ['required', 'string', 'max:30'],
            'name'       => ['required', 'string', 'max:100'],
            'location'   => ['nullable', 'string', 'max:150'],
            'latitude'   => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'  => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function authorizePoint(Request $request, AttendanceGatePoint $point): void
    {
        if ($request->user()?->factory_id && $request->user()->factory_id !== $point->factory_id) {
            abort(403);
        }
    }
}
