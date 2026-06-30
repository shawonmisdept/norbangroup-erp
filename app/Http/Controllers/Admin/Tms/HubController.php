<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HubController extends Controller
{
    public function __invoke(Request $request)
    {
        $modules = collect(config('tms.submodules', []))
            ->filter(fn ($mod, $key) => $key !== 'dashboard'
                && ($mod['status'] ?? '') === 'active'
                && $request->user()?->canViewTmsSubmodule($key));

        return view('admin.tms.hub', ['modules' => $modules]);
    }
}
