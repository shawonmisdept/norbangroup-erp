<?php

use App\Http\Controllers\Api\Hrm\ZKTecoIclockController;
use Illuminate\Support\Facades\Route;

/*
| ZKTeco SpeedFace V5L iClock / ADMS protocol (plain-text, no /api prefix).
| Device setup: Comm → Cloud Server Setting → Server Address = http://YOUR-IP:PORT
*/
Route::prefix('iclock')->group(function () {
    Route::get('/cdata', [ZKTecoIclockController::class, 'cdataGet'])->name('iclock.cdata.get');
    Route::post('/cdata', [ZKTecoIclockController::class, 'cdataPost'])->name('iclock.cdata.post');
    Route::get('/getrequest', [ZKTecoIclockController::class, 'getRequest'])->name('iclock.getrequest');
    Route::post('/devicecmd', [ZKTecoIclockController::class, 'deviceCmd'])->name('iclock.devicecmd');
    Route::match(['GET', 'POST'], '/registry', [ZKTecoIclockController::class, 'registry'])->name('iclock.registry');
});
