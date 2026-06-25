<?php

namespace App\Http\Controllers\Admin\Hrm\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HubController extends Controller
{
    public function __invoke(Request $request)
    {
        $modules = collect(config('hrm.attendance_submodules', []))
            ->filter(fn ($mod, $key) => ($mod['status'] ?? '') === 'active'
                && $request->user()?->canViewAttendanceSubmodule($key));

        return view('admin.hrm.attendance.hub', ['modules' => $modules]);
    }
}
