<?php

namespace App\Http\Controllers\Admin\Hrm\Salary;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HubController extends Controller
{
    public function __invoke(Request $request)
    {
        $modules = collect(config('hrm.salary_submodules', []))
            ->filter(fn ($sub, $key) => $request->user()->canViewSalarySubmodule($key))
            ->map(function ($sub, $key) use ($request) {
                return array_merge($sub, [
                    'key'        => $key,
                    'can_manage' => $request->user()->canManageSalarySubmodule($key),
                ]);
            });

        return view('admin.hrm.salary.hub', compact('modules'));
    }
}
