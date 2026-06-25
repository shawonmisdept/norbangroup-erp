<?php

namespace App\Http\Controllers\Admin\Hrm\Rmg;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HubController extends Controller
{
    public function __invoke(Request $request)
    {
        $modules = collect(config('hrm.rmg_submodules', []))
            ->filter(fn ($sub, $key) => $request->user()->canViewRmgSubmodule($key))
            ->map(function ($sub, $key) use ($request) {
                return array_merge($sub, [
                    'key'        => $key,
                    'can_manage' => $request->user()->canManageRmgSubmodule($key),
                ]);
            });

        return view('admin.hrm.rmg.hub', compact('modules'));
    }
}
