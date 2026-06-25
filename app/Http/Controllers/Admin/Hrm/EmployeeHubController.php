<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeHubController extends Controller
{
    public function __invoke(Request $request)
    {
        $modules = collect(config('hrm.employee_submodules', []))
            ->filter(fn ($sub, $key) => $request->user()->canViewEmployeeSubmodule($key))
            ->map(function ($sub, $key) use ($request) {
                return array_merge($sub, [
                    'key'        => $key,
                    'can_manage' => $request->user()->canManageEmployeeSubmodule($key),
                ]);
            });

        abort_if($modules->isEmpty(), 403, 'You do not have permission to access Employee modules.');

        return view('admin.hrm.employee.hub', compact('modules'));
    }
}
