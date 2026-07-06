<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware([])
                ->group(base_path('routes/iclock.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\EagerLoadAuthUserRelations::class,
        ]);

        $middleware->alias([
            'permission'          => \App\Http\Middleware\EnsureUserHasPermission::class,
            'master.permission'   => \App\Http\Middleware\EnsureMasterModulePermission::class,
            'master.any'          => \App\Http\Middleware\EnsureAnyMasterViewPermission::class,
            'hrm.master.permission' => \App\Http\Middleware\EnsureHrmMasterModulePermission::class,
            'hrm.master.any'        => \App\Http\Middleware\EnsureAnyHrmMasterViewPermission::class,
            'hrm.any'               => \App\Http\Middleware\EnsureAnyHrmViewPermission::class,
            'hrm.submodule'         => \App\Http\Middleware\EnsureHrmSubmodulePermission::class,
            'hrm.section-view'      => \App\Http\Middleware\EnsureHrmSectionViewPermission::class,
            'tms.any'               => \App\Http\Middleware\EnsureAnyTmsViewPermission::class,
            'adms.push'             => \App\Http\Middleware\EnsureAdmsPushToken::class,
            'tms.gps'               => \App\Http\Middleware\EnsureTmsGpsApiToken::class,
            'kb.view'               => \App\Http\Middleware\EnsureKbViewPermission::class,
            'kb.manage'             => \App\Http\Middleware\EnsureKbManagePermission::class,
            'factory.scope'         => \App\Http\Middleware\EnsureUserFactoryScope::class,
            'employee.portal'       => \App\Http\Middleware\EnsureLinkedPortalEmployee::class,
            'rental.portal'         => \App\Http\Middleware\EnsureLinkedRentalDriver::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('employee') || $request->is('employee/*')) {
                return route('employee.login');
            }

            if ($request->is('rental') || $request->is('rental/*')) {
                return route('rental.login');
            }

            return route('login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->is('employee') || $request->is('employee/*')) {
                return route('employee.dashboard');
            }

            if ($request->is('rental') || $request->is('rental/*')) {
                return route('rental.dashboard');
            }

            if ($request->is('login') && $request->user()) {
                $adminUrl = $request->user()->adminPortalUrl();

                if ($adminUrl) {
                    return $adminUrl;
                }
            }

            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.requirements.index');
            }

            return route('orders.create');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
