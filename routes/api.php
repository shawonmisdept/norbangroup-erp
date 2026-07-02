<?php

use App\Http\Controllers\Api\Hrm\AdmsPushController;
use Illuminate\Support\Facades\Route;

Route::prefix('hrm/adms')->middleware('adms.push')->group(function () {
    Route::post('/push', [AdmsPushController::class, 'store'])->name('api.hrm.adms.push');
    Route::post('/push/{device}', [AdmsPushController::class, 'store'])->name('api.hrm.adms.push.device')->whereNumber('device');
});

Route::prefix('tms/gps')->middleware('tms.gps')->group(function () {
    Route::post('/positions', [\App\Http\Controllers\Api\Tms\GpsPositionController::class, 'store'])->name('api.tms.gps.positions.store');
});
