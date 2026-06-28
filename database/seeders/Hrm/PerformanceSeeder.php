<?php

namespace Database\Seeders\Hrm;

use App\Models\Hrm\PerformanceBonusBand;
use App\Models\Hrm\PerformanceIncrementBand;
use App\Services\Hrm\PerformanceBonusBandService;
use App\Services\Hrm\PerformanceIncrementBandService;
use App\Services\Hrm\PerformanceTemplateService;
use Illuminate\Database\Seeder;

class PerformanceSeeder extends Seeder
{
    public function run(): void
    {
        app(PerformanceTemplateService::class)->ensureDefaultTemplate();

        $bonusBands = PerformanceBonusBand::query()->whereNull('factory_id')->count();
        if ($bonusBands === 0) {
            app(PerformanceBonusBandService::class)->seedDefaultBands(null);
        }

        $incrementBands = PerformanceIncrementBand::query()->whereNull('factory_id')->count();
        if ($incrementBands === 0) {
            app(PerformanceIncrementBandService::class)->seedDefaultBands(null);
        }
    }
}
