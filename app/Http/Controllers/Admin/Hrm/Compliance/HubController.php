<?php

namespace App\Http\Controllers\Admin\Hrm\Compliance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HubController extends Controller
{
    public function __invoke(Request $request)
    {
        $modules = collect(config('hrm.compliance_submodules', []))
            ->filter(fn ($sub, $key) => $request->user()->canViewComplianceSubmodule($key))
            ->map(function ($sub, $key) use ($request) {
                return array_merge($sub, [
                    'key'        => $key,
                    'can_manage' => $request->user()->canManageComplianceSubmodule($key),
                ]);
            });

        return view('admin.hrm.compliance.hub', compact('modules'));
    }
}
