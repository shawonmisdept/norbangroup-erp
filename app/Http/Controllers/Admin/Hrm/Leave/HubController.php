<?php

namespace App\Http\Controllers\Admin\Hrm\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HubController extends Controller
{
    public function __invoke(Request $request)
    {
        $modules = collect(config('hrm.leave_submodules', []))
            ->filter(fn ($sub, $key) => $request->user()->canViewLeaveSubmodule($key))
            ->map(function ($sub, $key) use ($request) {
                return array_merge($sub, [
                    'key'        => $key,
                    'can_manage' => $request->user()->canManageLeaveSubmodule($key),
                ]);
            });

        return view('admin.hrm.leave.hub', compact('modules'));
    }
}
