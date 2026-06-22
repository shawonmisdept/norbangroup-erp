<?php

use App\Http\Controllers\Admin\AppSettingsController;
use App\Http\Controllers\Admin\MasterController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\OrderController;
use App\Models\Order;
use Illuminate\Support\Facades\Route;

Route::get('/', [OrderController::class, 'create'])->name('orders.create');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/orders/success', [OrderController::class, 'success'])->name('orders.success');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::middleware('permission:orders.view')->group(function () {
        Route::get('/requirements', [OrderController::class, 'index'])->name('requirements.index');
        Route::get('/requirements/{order}', [OrderController::class, 'show'])->name('requirements.show');
    });

    Route::get('/requirements/{order}/files/{type}/{index}/download', [OrderController::class, 'downloadFile'])
        ->name('requirements.files.download')
        ->middleware('permission:orders.download')
        ->where('type', 'techpack|artwork');

    Route::get('/requirements/{order}/files/{type}/{index}/preview', [OrderController::class, 'previewFile'])
        ->name('requirements.files.preview')
        ->middleware('permission:orders.download')
        ->where('type', 'techpack|artwork');

    Route::patch('/requirements/{order}', [OrderController::class, 'update'])
        ->name('requirements.update')
        ->middleware('permission:orders.update');

    Route::delete('/requirements/{order}', [OrderController::class, 'destroy'])
        ->name('requirements.destroy')
        ->middleware('permission:orders.delete');

    Route::redirect('/orders', '/admin/requirements', 301);

    Route::get('/orders/{order}', function (Order $order) {
        return redirect()->route('admin.requirements.show', $order, 301);
    })->whereNumber('order');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    Route::middleware('permission:users.manage')->group(function () {
        Route::resource('users', UserController::class);
    });

    Route::middleware('permission:roles.manage')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings', [AppSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [AppSettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-mail', [AppSettingsController::class, 'sendTestMail'])->name('settings.test-mail');
    });

    Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
        Route::patch('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::patch('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
    });

    Route::middleware('master.any')->group(function () {
        Route::get('/masters', [MasterController::class, 'hub'])->name('masters.hub');
    });

    Route::middleware('master.permission:view')->group(function () {
        Route::get('/masters/{module}', [MasterController::class, 'index'])->name('masters.index');
        Route::get('/masters/{module}/{id}', [MasterController::class, 'show'])->name('masters.show')->whereNumber('id');
    });

    Route::middleware('master.permission:manage')->group(function () {
        Route::get('/masters/{module}/create', [MasterController::class, 'create'])->name('masters.create');
        Route::post('/masters/{module}', [MasterController::class, 'store'])->name('masters.store');
        Route::get('/masters/{module}/{id}/edit', [MasterController::class, 'edit'])->name('masters.edit')->whereNumber('id');
        Route::put('/masters/{module}/{id}', [MasterController::class, 'update'])->name('masters.update')->whereNumber('id');
        Route::delete('/masters/{module}/{id}', [MasterController::class, 'destroy'])->name('masters.destroy')->whereNumber('id');
    });
});
