<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesMasterModule;
use App\Http\Controllers\Controller;

class MasterController extends Controller
{
    use ManagesMasterModule;

    protected function masterModulesConfigKey(): string
    {
        return 'masters.modules';
    }

    protected function masterRoutePrefix(): string
    {
        return 'admin.masters';
    }

    protected function masterHubLabel(): string
    {
        return 'Master Data';
    }

    protected function masterPermissionNamespace(): string
    {
        return 'masters';
    }
}
