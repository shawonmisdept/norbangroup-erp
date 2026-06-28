<?php

namespace App\Http\Controllers\Admin\Hrm\Performance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HubController extends Controller
{
    use ScopesHrmFactory;

    public function __invoke(Request $request)
    {
        $modules = collect(config('hrm.performance_submodules', []))
            ->filter(fn ($sub, $key) => $request->user()->canViewPerformanceSubmodule($key))
            ->map(function ($sub, $key) use ($request) {
                return array_merge($sub, [
                    'key'        => $key,
                    'can_manage' => $request->user()->canManagePerformanceSubmodule($key),
                ]);
            });

        return view('admin.hrm.performance.hub', [
            'modules'           => $modules,
            'factories'         => $this->factoryOptions($request),
            'scopedFactoryName' => $this->scopedFactoryName($request),
        ]);
    }
}
