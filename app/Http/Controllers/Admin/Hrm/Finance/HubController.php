<?php

namespace App\Http\Controllers\Admin\Hrm\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HubController extends Controller
{
    public function __invoke(Request $request)
    {
        $modules = collect(config('hrm.finance_submodules', []))
            ->filter(fn ($sub, $key) => $request->user()->canViewFinanceSubmodule($key))
            ->map(function ($sub, $key) use ($request) {
                return array_merge($sub, [
                    'key'        => $key,
                    'can_manage' => $request->user()->canManageFinanceSubmodule($key),
                ]);
            });

        return view('admin.hrm.finance.hub', compact('modules'));
    }
}
