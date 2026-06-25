<?php

namespace App\Http\Controllers\Admin\Hrm\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PlannedController extends Controller
{
    public function __invoke(Request $request, string $module)
    {
        $config = config("hrm.attendance_submodules.{$module}");

        abort_unless($config && ($config['status'] ?? '') === 'planned', 404);

        return view('admin.hrm.attendance.planned', [
            'module'      => $module,
            'config'      => $config,
            'hubRoute'    => 'admin.hrm.attendance.hub',
            'sectionLabel'=> 'Attendance',
        ]);
    }
}
