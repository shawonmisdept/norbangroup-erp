<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Admin\Concerns\ManagesMasterModule;
use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;

class HrmMasterController extends Controller
{
    use ManagesMasterModule, ScopesHrmFactory;

    protected function masterModulesConfigKey(): string
    {
        return 'hrm.modules';
    }

    protected function masterRoutePrefix(): string
    {
        return 'admin.hrm.masters';
    }

    protected function masterHubLabel(): string
    {
        return 'HRM Master Data';
    }

    protected function masterPermissionNamespace(): string
    {
        return 'hrm';
    }
}
