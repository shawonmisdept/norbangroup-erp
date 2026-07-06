<?php

namespace App\Http\Controllers\Admin\Kb;

use App\Http\Controllers\Controller;
use App\Services\KbAccessService;
use Illuminate\Http\Request;

class HubController extends Controller
{
    public function __invoke(Request $request, KbAccessService $access)
    {
        return view('admin.kb.hub', [
            'modules'   => $access->visibleModulesFor($request->user()),
            'canManage' => $access->canManageKb($request->user()),
        ]);
    }
}
