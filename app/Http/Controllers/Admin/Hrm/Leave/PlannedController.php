<?php

namespace App\Http\Controllers\Admin\Hrm\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PlannedController extends Controller
{
    public function __invoke(Request $request, string $module)
    {
        $config = config("hrm.leave_submodules.{$module}");

        abort_unless($config && ($config['status'] ?? '') === 'planned', 404);

        abort_unless($request->user()->canViewLeaveSubmodule($module), 403);

        return view('admin.hrm.partials.planned', [
            'module'      => $module,
            'label'       => $config['label'],
            'description' => $config['description'],
            'hubRoute'    => 'admin.hrm.leave.hub',
            'section'     => 'Leave',
        ]);
    }
}
