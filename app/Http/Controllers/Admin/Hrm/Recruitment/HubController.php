<?php

namespace App\Http\Controllers\Admin\Hrm\Recruitment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HubController extends Controller
{
    public function __invoke(Request $request)
    {
        $modules = collect(config('hrm.recruitment_submodules', []))
            ->filter(fn ($sub, $key) => $request->user()->canViewRecruitmentSubmodule($key))
            ->map(function ($sub, $key) use ($request) {
                return array_merge($sub, [
                    'key'        => $key,
                    'can_manage' => $request->user()->canManageRecruitmentSubmodule($key),
                ]);
            });

        abort_if($modules->isEmpty(), 403, 'You do not have permission to access Recruitment modules.');

        return view('admin.hrm.recruitment.hub', compact('modules'));
    }
}
