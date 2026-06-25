<?php

use App\Http\Controllers\Api\Hrm\AdmsPushController;
use Illuminate\Support\Facades\Route;

Route::prefix('hrm/adms')->middleware('adms.push')->group(function () {
    Route::post('/push', [AdmsPushController::class, 'store'])->name('api.hrm.adms.push');
    Route::post('/push/{device}', [AdmsPushController::class, 'store'])->name('api.hrm.adms.push.device')->whereNumber('device');
});
