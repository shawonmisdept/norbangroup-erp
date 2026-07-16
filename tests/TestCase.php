<?php

namespace Tests;

use App\Models\AppSetting;
use App\Support\TmsDriverVehiclePivot;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::clearCache();
        TmsDriverVehiclePivot::resetCache();
    }
}
