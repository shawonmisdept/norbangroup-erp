<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class TmsDriverVehiclePivot
{
    private static ?bool $available = null;

    public static function available(): bool
    {
        return self::$available ??= Schema::hasTable('tms_driver_vehicles');
    }
}
