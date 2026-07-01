<?php

use App\Http\Controllers\Admin\AppSettingsController;
use App\Http\Controllers\Admin\Hrm\AttendanceController;
use App\Http\Controllers\Admin\Hrm\Attendance\HubController as AttendanceHubController;
use App\Http\Controllers\Admin\Hrm\Attendance\GatePointController as AttendanceGatePointController;
use App\Http\Controllers\Admin\Hrm\Attendance\HalfDayEntryController as AttendanceHalfDayEntryController;
use App\Http\Controllers\Admin\Hrm\Attendance\LateAcceptanceController as AttendanceLateAcceptanceController;
use App\Http\Controllers\Admin\Hrm\Attendance\ManualPunchController as AttendanceManualPunchController;
use App\Http\Controllers\Admin\Hrm\Attendance\PlannedController as AttendancePlannedController;
use App\Http\Controllers\Admin\Hrm\Attendance\PolicyController as AttendancePolicyController;
use App\Http\Controllers\Admin\Hrm\Attendance\ReportController as AttendanceReportController;
use App\Http\Controllers\Admin\Hrm\DashboardController as HrmDashboardController;
use App\Http\Controllers\Admin\Hrm\EmployeeController;
use App\Http\Controllers\Admin\Hrm\EmployeeHubController;
use App\Http\Controllers\Admin\Hrm\EmployeePortalController;
use App\Http\Controllers\Admin\Hrm\HrmMasterController;
use App\Http\Controllers\Admin\Hrm\Performance\BonusBandController as PerformanceBonusBandController;
use App\Http\Controllers\Admin\Hrm\Performance\BonusRunController as PerformanceBonusRunController;
use App\Http\Controllers\Admin\Hrm\Performance\IncrementBandController as PerformanceIncrementBandController;
use App\Http\Controllers\Admin\Hrm\Performance\IncrementRunController as PerformanceIncrementRunController;
use App\Http\Controllers\Admin\Hrm\Performance\CycleController as PerformanceCycleController;
use App\Http\Controllers\Admin\Hrm\Performance\HubController as PerformanceHubController;
use App\Http\Controllers\Admin\Hrm\Performance\ReviewController as PerformanceReviewController;
use App\Http\Controllers\Admin\Hrm\Performance\TemplateController as PerformanceTemplateController;
use App\Http\Controllers\Admin\Hrm\Leave\AllocationController as LeaveAllocationController;
use App\Http\Controllers\Admin\Hrm\Leave\BulkEntryController as LeaveBulkEntryController;
use App\Http\Controllers\Admin\Hrm\Leave\HubController as LeaveHubController;
use App\Http\Controllers\Admin\Hrm\Leave\MaternityRuleController;
use App\Http\Controllers\Admin\Hrm\Leave\MaternityTransactionController;
use App\Http\Controllers\Admin\Hrm\Leave\OpeningBalanceController;
use App\Http\Controllers\Admin\Hrm\Leave\PlannedController as LeavePlannedController;
use App\Http\Controllers\Admin\Hrm\Leave\PolicyController as LeavePolicyController;
use App\Http\Controllers\Admin\Hrm\Leave\RuleController as LeaveRuleController;
use App\Http\Controllers\Admin\Hrm\Leave\TransactionController as LeaveTransactionController;
use App\Http\Controllers\Admin\Hrm\Salary\IncrementBulkController;
use App\Http\Controllers\Admin\Hrm\Salary\IncrementRuleController;
use App\Http\Controllers\Admin\Hrm\Salary\IncrementUploadController;
use App\Http\Controllers\Admin\Hrm\Salary\CloseController as SalaryCloseController;
use App\Http\Controllers\Admin\Hrm\Salary\EmployeeSalaryController;
use App\Http\Controllers\Admin\Hrm\Salary\GradeController as SalaryGradeController;
use App\Http\Controllers\Admin\Hrm\Salary\GradeDetailController;
use App\Http\Controllers\Admin\Hrm\Salary\HeadController as SalaryHeadController;
use App\Http\Controllers\Admin\Hrm\Salary\HubController as SalaryHubController;
use App\Http\Controllers\Admin\Hrm\Salary\PlannedController as SalaryPlannedController;
use App\Http\Controllers\Admin\Hrm\Salary\ProcessController as SalaryProcessController;
use App\Http\Controllers\Admin\Hrm\Salary\UploadController as SalaryUploadController;
use App\Http\Controllers\Admin\Hrm\Compliance\AgeVerificationController as ComplianceAgeVerificationController;
use App\Http\Controllers\Admin\Hrm\Compliance\BonusController as ComplianceBonusController;
use App\Http\Controllers\Admin\Hrm\Compliance\GratuityController as ComplianceGratuityController;
use App\Http\Controllers\Admin\Hrm\Compliance\HubController as ComplianceHubController;
use App\Http\Controllers\Admin\Hrm\Compliance\RegisterController as ComplianceRegisterController;
use App\Http\Controllers\Admin\Hrm\Compliance\WorkingHoursController as ComplianceWorkingHoursController;
use App\Http\Controllers\Admin\Hrm\Finance\HubController as FinanceHubController;
use App\Http\Controllers\Admin\Hrm\Finance\BulkAdvanceController as FinanceBulkAdvanceController;
use App\Http\Controllers\Admin\Hrm\Finance\FinalSettlementController as FinanceFinalSettlementController;
use App\Http\Controllers\Admin\Hrm\Finance\LoanController as FinanceLoanController;
use App\Http\Controllers\Admin\Hrm\Finance\PfController as FinancePfController;
use App\Http\Controllers\Admin\Hrm\Finance\TaxController as FinanceTaxController;
use App\Http\Controllers\Admin\Hrm\Recruitment\HubController as RecruitmentHubController;
use App\Http\Controllers\Admin\Hrm\Rmg\ExportController as RmgExportController;
use App\Http\Controllers\Admin\Hrm\Rmg\GatePassController as RmgGatePassController;
use App\Http\Controllers\Admin\Hrm\Rmg\GenericRmgController;
use App\Http\Controllers\Admin\Hrm\Rmg\HubController as RmgHubController;
use App\Http\Controllers\Admin\Hrm\Rmg\ManpowerPlanningController as RmgManpowerPlanningController;
use App\Http\Controllers\Admin\Hrm\Rmg\ProxyPunchController as RmgProxyPunchController;
use App\Http\Controllers\Admin\Hrm\Rmg\WorkerTransferController as RmgWorkerTransferController;
use App\Http\Controllers\Admin\Hrm\Attendance\RosterController as AttendanceRosterController;
use App\Http\Controllers\Admin\MasterController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Employee\AttendanceController as EmployeeAttendanceController;
use App\Http\Controllers\Employee\CheckInController as EmployeeCheckInController;
use App\Http\Controllers\Employee\Auth\LoginController as EmployeeLoginController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\LeaveController as EmployeeLeaveController;
use App\Http\Controllers\Employee\LateAcceptanceController as EmployeeLateAcceptanceController;
use App\Http\Controllers\Employee\LoanController as EmployeeLoanController;
use App\Http\Controllers\Employee\PfController as EmployeePfController;
use App\Http\Controllers\Employee\PayslipController as EmployeePayslipController;
use App\Http\Controllers\Employee\PerformanceController as EmployeePerformanceController;
use App\Http\Controllers\Employee\RosterController as EmployeeRosterController;
use App\Http\Controllers\Employee\NotificationController as EmployeeNotificationController;
use App\Http\Controllers\Employee\PushSubscriptionController;
use App\Http\Controllers\Employee\ProfileController as EmployeeProfileController;
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

    Route::patch('/requirements/{order}/workflow', [OrderController::class, 'updateWorkflow'])
        ->name('requirements.workflow')
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
        Route::resource('roles', RoleController::class);
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings', [AppSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [AppSettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-mail', [AppSettingsController::class, 'sendTestMail'])->name('settings.test-mail');
        Route::post('/settings/test-sms', [AppSettingsController::class, 'sendTestSms'])->name('settings.test-sms');
        Route::post('/settings/test-whatsapp', [AppSettingsController::class, 'sendTestWhatsApp'])->name('settings.test-whatsapp');
    });

    Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
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

    Route::prefix('hrm')->name('hrm.')->middleware('factory.scope')->group(function () {
        Route::middleware('hrm.any')->group(function () {
            Route::get('/', [HrmDashboardController::class, 'index'])->name('dashboard');
            Route::get('/today-attendance', [HrmDashboardController::class, 'todayAttendance'])->name('dashboard.today-attendance');
        });

        Route::middleware('hrm.master.any')->group(function () {
            Route::get('/masters', [HrmMasterController::class, 'hub'])->name('masters.hub');
        });

        Route::middleware('hrm.master.permission:view')->group(function () {
            Route::get('/masters/{module}', [HrmMasterController::class, 'index'])->name('masters.index');
            Route::get('/masters/{module}/{id}', [HrmMasterController::class, 'show'])->name('masters.show')->whereNumber('id');
        });

        Route::middleware('hrm.master.permission:manage')->group(function () {
            Route::get('/masters/{module}/create', [HrmMasterController::class, 'create'])->name('masters.create');
            Route::post('/masters/{module}', [HrmMasterController::class, 'store'])->name('masters.store');
            Route::get('/masters/{module}/{id}/edit', [HrmMasterController::class, 'edit'])->name('masters.edit')->whereNumber('id');
            Route::put('/masters/{module}/{id}', [HrmMasterController::class, 'update'])->name('masters.update')->whereNumber('id');
            Route::delete('/masters/{module}/{id}', [HrmMasterController::class, 'destroy'])->name('masters.destroy')->whereNumber('id');
        });

        Route::middleware('hrm.any')->group(function () {
            Route::get('/employee', EmployeeHubController::class)->name('employee.hub');
            Route::get('/recruitment', RecruitmentHubController::class)->name('recruitment.hub');
        });

        Route::middleware('permission:hrm.employees.manage')->group(function () {
            Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
            Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
            Route::post('/employees/{employee}/portal', [EmployeePortalController::class, 'store'])->name('employees.portal.store');
            Route::put('/employees/{employee}/portal', [EmployeePortalController::class, 'update'])->name('employees.portal.update');
            Route::delete('/employees/{employee}/portal', [EmployeePortalController::class, 'destroy'])->name('employees.portal.destroy');
        });

        Route::middleware('permission:hrm.employees.view')->group(function () {
            Route::get('/employee/dashboard', [\App\Http\Controllers\Admin\Hrm\Employee\DashboardController::class, 'index'])->name('employee.dashboard');
            Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
            Route::get('/employees/{employee}/id-card', [EmployeeController::class, 'idCard'])->name('employees.id-card');
        });

        Route::middleware('permission:hrm.employees.separation.view')->group(function () {
            Route::get('/separations', [\App\Http\Controllers\Admin\Hrm\SeparationController::class, 'index'])->name('separations.index');
            Route::get('/separations/export', [\App\Http\Controllers\Admin\Hrm\SeparationController::class, 'export'])->name('separations.export');
            Route::get('/separations/{separation}', [\App\Http\Controllers\Admin\Hrm\SeparationController::class, 'show'])->name('separations.show')->whereNumber('separation');
        });

        Route::middleware('permission:hrm.employees.separation.manage')->group(function () {
            Route::get('/separations/create', [\App\Http\Controllers\Admin\Hrm\SeparationController::class, 'create'])->name('separations.create');
            Route::post('/separations', [\App\Http\Controllers\Admin\Hrm\SeparationController::class, 'store'])->name('separations.store');
            Route::delete('/separations/{separation}', [\App\Http\Controllers\Admin\Hrm\SeparationController::class, 'cancel'])->name('separations.cancel')->whereNumber('separation');
        });

        Route::middleware('permission:hrm.employees.separation.approve')->group(function () {
            Route::post('/separations/{separation}/approve', [\App\Http\Controllers\Admin\Hrm\SeparationController::class, 'approve'])->name('separations.approve')->whereNumber('separation');
            Route::post('/separations/{separation}/reject', [\App\Http\Controllers\Admin\Hrm\SeparationController::class, 'reject'])->name('separations.reject')->whereNumber('separation');
            Route::post('/separations/{separation}/exit-data', [\App\Http\Controllers\Admin\Hrm\SeparationController::class, 'saveExitData'])->name('separations.exit-data')->whereNumber('separation');
        });

        Route::middleware('permission:hrm.employees.promotion.view')->group(function () {
            Route::get('/promotions', [\App\Http\Controllers\Admin\Hrm\PromotionController::class, 'index'])->name('promotions.index');
            Route::get('/promotions/export', [\App\Http\Controllers\Admin\Hrm\PromotionController::class, 'export'])->name('promotions.export');
            Route::get('/promotions/{promotion}', [\App\Http\Controllers\Admin\Hrm\PromotionController::class, 'show'])->name('promotions.show')->whereNumber('promotion');
        });

        Route::middleware('permission:hrm.employees.promotion.manage')->group(function () {
            Route::get('/promotions/create', [\App\Http\Controllers\Admin\Hrm\PromotionController::class, 'create'])->name('promotions.create');
            Route::post('/promotions', [\App\Http\Controllers\Admin\Hrm\PromotionController::class, 'store'])->name('promotions.store');
            Route::delete('/promotions/{promotion}', [\App\Http\Controllers\Admin\Hrm\PromotionController::class, 'cancel'])->name('promotions.cancel')->whereNumber('promotion');
        });

        Route::middleware('permission:hrm.employees.promotion.approve')->group(function () {
            Route::post('/promotions/{promotion}/approve', [\App\Http\Controllers\Admin\Hrm\PromotionController::class, 'approve'])->name('promotions.approve')->whereNumber('promotion');
            Route::post('/promotions/{promotion}/reject', [\App\Http\Controllers\Admin\Hrm\PromotionController::class, 'reject'])->name('promotions.reject')->whereNumber('promotion');
        });

        Route::middleware('permission:hrm.employees.letters.view')->group(function () {
            Route::get('/letters', [\App\Http\Controllers\Admin\Hrm\LetterController::class, 'index'])->name('letters.index');
            Route::get('/letters/{letter}', [\App\Http\Controllers\Admin\Hrm\LetterController::class, 'show'])->name('letters.show')->whereNumber('letter');
            Route::get('/letters/{letter}/print', [\App\Http\Controllers\Admin\Hrm\LetterController::class, 'print'])->name('letters.print')->whereNumber('letter');
            Route::get('/letter-templates', [\App\Http\Controllers\Admin\Hrm\LetterTemplateController::class, 'index'])->name('letter-templates.index');
        });

        Route::middleware('permission:hrm.employees.letters.manage')->group(function () {
            Route::get('/letters/create', [\App\Http\Controllers\Admin\Hrm\LetterController::class, 'create'])->name('letters.create');
            Route::post('/letters', [\App\Http\Controllers\Admin\Hrm\LetterController::class, 'store'])->name('letters.store');
            Route::post('/letters/{letter}/void', [\App\Http\Controllers\Admin\Hrm\LetterController::class, 'void'])->name('letters.void')->whereNumber('letter');
            Route::post('/letters/{letter}/reissue', [\App\Http\Controllers\Admin\Hrm\LetterController::class, 'reissue'])->name('letters.reissue')->whereNumber('letter');
            Route::get('/letter-templates/create', [\App\Http\Controllers\Admin\Hrm\LetterTemplateController::class, 'create'])->name('letter-templates.create');
            Route::post('/letter-templates', [\App\Http\Controllers\Admin\Hrm\LetterTemplateController::class, 'store'])->name('letter-templates.store');
            Route::get('/letter-templates/{letterTemplate}/edit', [\App\Http\Controllers\Admin\Hrm\LetterTemplateController::class, 'edit'])->name('letter-templates.edit')->whereNumber('letterTemplate');
            Route::put('/letter-templates/{letterTemplate}', [\App\Http\Controllers\Admin\Hrm\LetterTemplateController::class, 'update'])->name('letter-templates.update')->whereNumber('letterTemplate');
            Route::delete('/letter-templates/{letterTemplate}', [\App\Http\Controllers\Admin\Hrm\LetterTemplateController::class, 'destroy'])->name('letter-templates.destroy')->whereNumber('letterTemplate');
        });

        Route::middleware('permission:hrm.employees.discipline.view')->group(function () {
            Route::get('/discipline', [\App\Http\Controllers\Admin\Hrm\DisciplinaryController::class, 'index'])->name('discipline.index');
            Route::get('/discipline/{discipline}', [\App\Http\Controllers\Admin\Hrm\DisciplinaryController::class, 'show'])->name('discipline.show')->whereNumber('discipline');
        });

        Route::middleware('permission:hrm.employees.discipline.manage')->group(function () {
            Route::get('/discipline/create', [\App\Http\Controllers\Admin\Hrm\DisciplinaryController::class, 'create'])->name('discipline.create');
            Route::post('/discipline', [\App\Http\Controllers\Admin\Hrm\DisciplinaryController::class, 'store'])->name('discipline.store');
            Route::post('/discipline/{discipline}/close', [\App\Http\Controllers\Admin\Hrm\DisciplinaryController::class, 'close'])->name('discipline.close')->whereNumber('discipline');
        });

        Route::middleware('permission:hrm.recruitment.postings.view')->group(function () {
            Route::get('/recruitment/postings', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'index'])->name('recruitment.postings.index');
            Route::get('/recruitment/postings/export', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'export'])->name('recruitment.postings.export');
            Route::get('/recruitment/postings/form-options', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'formOptionsJson'])->name('recruitment.postings.form-options');
            Route::get('/recruitment/postings/{posting}', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'show'])->name('recruitment.postings.show')->whereNumber('posting');
        });

        Route::middleware('permission:hrm.recruitment.postings.manage')->group(function () {
            Route::get('/recruitment/postings/create', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'create'])->name('recruitment.postings.create');
            Route::get('/recruitment/postings/bulk/create', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'bulkCreateForm'])->name('recruitment.postings.bulk.create');
            Route::post('/recruitment/postings/bulk', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'bulkStore'])->name('recruitment.postings.bulk.store');
            Route::post('/recruitment/postings', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'store'])->name('recruitment.postings.store');
            Route::get('/recruitment/postings/{posting}/edit', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'edit'])->name('recruitment.postings.edit')->whereNumber('posting');
            Route::put('/recruitment/postings/{posting}', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'update'])->name('recruitment.postings.update')->whereNumber('posting');
            Route::delete('/recruitment/postings/{posting}', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'destroy'])->name('recruitment.postings.destroy')->whereNumber('posting');
            Route::post('/recruitment/postings/{posting}/publish', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'publish'])->name('recruitment.postings.publish')->whereNumber('posting');
            Route::post('/recruitment/postings/{posting}/close', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'close'])->name('recruitment.postings.close')->whereNumber('posting');
            Route::post('/recruitment/postings/{posting}/reopen', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'reopen'])->name('recruitment.postings.reopen')->whereNumber('posting');
            Route::post('/recruitment/postings/{posting}/duplicate', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'duplicate'])->name('recruitment.postings.duplicate')->whereNumber('posting');
        });

        Route::middleware('permission:hrm.recruitment.postings.approve')->group(function () {
            Route::post('/recruitment/postings/{posting}/approve', [\App\Http\Controllers\Admin\Hrm\Recruitment\JobPostingController::class, 'approve'])->name('recruitment.postings.approve')->whereNumber('posting');
        });

        Route::middleware('permission:hrm.recruitment.applications.view')->group(function () {
            Route::get('/recruitment/dashboard', [\App\Http\Controllers\Admin\Hrm\Recruitment\DashboardController::class, 'index'])->name('recruitment.dashboard');
            Route::get('/recruitment/applications', [\App\Http\Controllers\Admin\Hrm\Recruitment\ApplicationController::class, 'index'])->name('recruitment.applications.index');
            Route::get('/recruitment/applications/export', [\App\Http\Controllers\Admin\Hrm\Recruitment\ApplicationController::class, 'export'])->name('recruitment.applications.export');
            Route::get('/recruitment/applications/{application}', [\App\Http\Controllers\Admin\Hrm\Recruitment\ApplicationController::class, 'show'])->name('recruitment.applications.show')->whereNumber('application');
            Route::get('/recruitment/offer-letters/{offerLetter}', [\App\Http\Controllers\Admin\Hrm\Recruitment\OfferLetterController::class, 'show'])->name('recruitment.offer-letters.show')->whereNumber('offerLetter');
            Route::get('/recruitment/offer-letters/{offerLetter}/print', [\App\Http\Controllers\Admin\Hrm\Recruitment\OfferLetterController::class, 'print'])->name('recruitment.offer-letters.print')->whereNumber('offerLetter');
        });

        Route::middleware('permission:hrm.recruitment.applications.manage')->group(function () {
            Route::get('/recruitment/applications/create', [\App\Http\Controllers\Admin\Hrm\Recruitment\ApplicationController::class, 'create'])->name('recruitment.applications.create');
            Route::post('/recruitment/applications', [\App\Http\Controllers\Admin\Hrm\Recruitment\ApplicationController::class, 'store'])->name('recruitment.applications.store');
            Route::post('/recruitment/applications/{application}/status', [\App\Http\Controllers\Admin\Hrm\Recruitment\ApplicationController::class, 'updateStatus'])->name('recruitment.applications.status')->whereNumber('application');
            Route::post('/recruitment/applications/{application}/interviews', [\App\Http\Controllers\Admin\Hrm\Recruitment\ApplicationController::class, 'scheduleInterview'])->name('recruitment.applications.interviews.store')->whereNumber('application');
            Route::post('/recruitment/applications/{application}/interviews/{interview}/complete', [\App\Http\Controllers\Admin\Hrm\Recruitment\ApplicationController::class, 'completeInterview'])->name('recruitment.applications.interviews.complete')->whereNumber(['application', 'interview']);
            Route::get('/recruitment/applications/{application}/offer-letter', [\App\Http\Controllers\Admin\Hrm\Recruitment\OfferLetterController::class, 'create'])->name('recruitment.applications.offer-letter.create')->whereNumber('application');
            Route::post('/recruitment/applications/{application}/offer-letter', [\App\Http\Controllers\Admin\Hrm\Recruitment\OfferLetterController::class, 'store'])->name('recruitment.applications.offer-letter.store')->whereNumber('application');
        });

        Route::middleware('permission:hrm.recruitment.applications.convert')->group(function () {
            Route::post('/recruitment/applications/{application}/convert', [\App\Http\Controllers\Admin\Hrm\Recruitment\ApplicationController::class, 'convert'])->name('recruitment.applications.convert')->whereNumber('application');
        });

        Route::middleware('permission:hrm.attendance.view')->group(function () {
            Route::get('/attendance', AttendanceHubController::class)->name('attendance.hub');
            Route::get('/attendance/dashboard', [\App\Http\Controllers\Admin\Hrm\Attendance\DashboardController::class, 'index'])->name('attendance.dashboard');
            Route::get('/attendance/planned/{module}', AttendancePlannedController::class)->name('attendance.planned');
            Route::get('/attendance/punches', [AttendanceController::class, 'punches'])->name('attendance.punches');
            Route::get('/attendance/daily', [AttendanceController::class, 'daily'])->name('attendance.daily');
            Route::get('/attendance/periods', [AttendanceController::class, 'periods'])->name('attendance.periods');
            Route::get('/attendance/periods/{period}', [AttendanceController::class, 'showPeriod'])->name('attendance.periods.show');
            Route::get('/attendance/policy', [AttendancePolicyController::class, 'index'])->name('attendance.policy.index');
            Route::get('/attendance/late-acceptance', [AttendanceLateAcceptanceController::class, 'index'])->name('attendance.late-acceptance.index');
            Route::get('/attendance/late-acceptance/{lateAcceptance}', [AttendanceLateAcceptanceController::class, 'show'])->name('attendance.late-acceptance.show');
            Route::get('/attendance/half-day-entry', [AttendanceHalfDayEntryController::class, 'index'])->name('attendance.half-day-entry.index');
            Route::get('/attendance/manual-punch', [AttendanceManualPunchController::class, 'index'])->name('attendance.manual-punch.index');
            Route::get('/attendance/gate-points', [AttendanceGatePointController::class, 'index'])->name('attendance.gate-points.index');
            Route::get('/attendance/gate-points/{gatePoint}/qr', [AttendanceGatePointController::class, 'qr'])->name('attendance.gate-points.qr');
            Route::get('/attendance/reports', [AttendanceReportController::class, 'index'])->name('attendance.reports.index');
            Route::get('/attendance/reports/export', [AttendanceReportController::class, 'export'])->name('attendance.reports.export');
            Route::get('/attendance/reports/employee/{employee}', [AttendanceReportController::class, 'employeeCalendar'])->name('attendance.reports.employee');
            Route::get('/attendance/roster', [AttendanceRosterController::class, 'index'])->name('attendance.roster.index');
            Route::get('/attendance/roster/variance', [AttendanceRosterController::class, 'variance'])->name('attendance.roster.variance');
            Route::get('/attendance/roster/variance/export', [AttendanceRosterController::class, 'exportVariance'])->name('attendance.roster.variance.export');
            Route::get('/attendance/roster/{roster}', [AttendanceRosterController::class, 'show'])->name('attendance.roster.show')->whereNumber('roster');
        });

        Route::middleware('permission:hrm.attendance.sync')->group(function () {
            Route::get('/attendance/sync', [AttendanceController::class, 'sync'])->name('attendance.sync.index');
            Route::get('/attendance/sync/failures', [AttendanceController::class, 'syncFailures'])->name('attendance.sync.failures');
            Route::post('/attendance/sync-all', [AttendanceController::class, 'syncAll'])->name('attendance.sync-all');
            Route::post('/attendance/devices/{device}/sync', [AttendanceController::class, 'syncDevice'])->name('attendance.devices.sync');
        });

        Route::middleware('permission:hrm.attendance.manage')->group(function () {
            Route::get('/attendance/half-day-entry/create', [AttendanceHalfDayEntryController::class, 'create'])->name('attendance.half-day-entry.create');
            Route::post('/attendance/half-day-entry', [AttendanceHalfDayEntryController::class, 'store'])->name('attendance.half-day-entry.store');
            Route::get('/attendance/manual-punch/create', [AttendanceManualPunchController::class, 'create'])->name('attendance.manual-punch.create');
            Route::post('/attendance/manual-punch', [AttendanceManualPunchController::class, 'store'])->name('attendance.manual-punch.store');
            Route::delete('/attendance/manual-punch/{manualPunch}', [AttendanceManualPunchController::class, 'destroy'])->name('attendance.manual-punch.destroy');
            Route::delete('/attendance/half-day-entry/{halfDayEntry}', [AttendanceHalfDayEntryController::class, 'destroy'])->name('attendance.half-day-entry.destroy');
            Route::get('/attendance/gate-points/create', [AttendanceGatePointController::class, 'create'])->name('attendance.gate-points.create');
            Route::post('/attendance/gate-points', [AttendanceGatePointController::class, 'store'])->name('attendance.gate-points.store');
            Route::get('/attendance/gate-points/{gatePoint}/edit', [AttendanceGatePointController::class, 'edit'])->name('attendance.gate-points.edit');
            Route::put('/attendance/gate-points/{gatePoint}', [AttendanceGatePointController::class, 'update'])->name('attendance.gate-points.update');
            Route::get('/attendance/policy/{policy}/edit', [AttendancePolicyController::class, 'edit'])->name('attendance.policy.edit');
            Route::put('/attendance/policy/{policy}', [AttendancePolicyController::class, 'update'])->name('attendance.policy.update');
            Route::post('/attendance/process', [AttendanceController::class, 'process'])->name('attendance.process');
            Route::post('/attendance/process-today', [AttendanceController::class, 'processToday'])->name('attendance.process-today');
            Route::post('/attendance/periods/{period}/freeze', [AttendanceController::class, 'freezePeriod'])->name('attendance.periods.freeze');
            Route::get('/attendance/roster/create', [AttendanceRosterController::class, 'create'])->name('attendance.roster.create');
            Route::post('/attendance/roster', [AttendanceRosterController::class, 'store'])->name('attendance.roster.store');
            Route::post('/attendance/roster/{roster}/assign', [AttendanceRosterController::class, 'assign'])->name('attendance.roster.assign')->whereNumber('roster');
            Route::post('/attendance/roster/{roster}/publish', [AttendanceRosterController::class, 'publish'])->name('attendance.roster.publish')->whereNumber('roster');
            Route::get('/attendance/roster/{roster}/import-template', [AttendanceRosterController::class, 'importTemplate'])->name('attendance.roster.import-template')->whereNumber('roster');
            Route::post('/attendance/roster/{roster}/import', [AttendanceRosterController::class, 'import'])->name('attendance.roster.import')->whereNumber('roster');
        });

        Route::middleware('permission:hrm.attendance.approve')->group(function () {
            Route::post('/attendance/late-acceptance/{lateAcceptance}/approve', [AttendanceLateAcceptanceController::class, 'approve'])->name('attendance.late-acceptance.approve');
            Route::post('/attendance/late-acceptance/{lateAcceptance}/reject', [AttendanceLateAcceptanceController::class, 'reject'])->name('attendance.late-acceptance.reject');
        });

        Route::redirect('/attendance/index', '/admin/hrm/attendance/sync');

        // ── Leave sub-modules ──
        Route::middleware('permission:hrm.leave.view')->group(function () {
            Route::get('/leave', LeaveHubController::class)->name('leave.hub');
            Route::get('/leave/dashboard', [\App\Http\Controllers\Admin\Hrm\Leave\DashboardController::class, 'index'])->name('leave.dashboard');
            Route::get('/leave/planned/{module}', LeavePlannedController::class)->name('leave.planned');

            Route::get('/leave/policies', [LeavePolicyController::class, 'index'])->name('leave.policies.index');
            Route::get('/leave/rules', [LeaveRuleController::class, 'index'])->name('leave.rules.index');
            Route::get('/leave/maternity-rules', [MaternityRuleController::class, 'index'])->name('leave.maternity-rules.index');
            Route::get('/leave/opening-balances', [OpeningBalanceController::class, 'index'])->name('leave.opening-balances.index');
            Route::get('/leave/transactions', [LeaveTransactionController::class, 'index'])->name('leave.transactions.index');
            Route::get('/leave/transactions/{transaction}', [LeaveTransactionController::class, 'show'])->name('leave.transactions.show');
            Route::get('/leave/allocation', [LeaveAllocationController::class, 'index'])->name('leave.allocation.index');
            Route::get('/leave/bulk-entry', [LeaveBulkEntryController::class, 'index'])->name('leave.bulk-entry.index');
            Route::get('/leave/maternity-transactions', [MaternityTransactionController::class, 'index'])->name('leave.maternity-transactions.index');
            Route::get('/leave/maternity-transactions/{maternityTransaction}', [MaternityTransactionController::class, 'show'])->name('leave.maternity-transactions.show');
        });

        Route::middleware('permission:hrm.leave.manage')->group(function () {
            Route::get('/leave/policies/create', [LeavePolicyController::class, 'create'])->name('leave.policies.create');
            Route::post('/leave/policies', [LeavePolicyController::class, 'store'])->name('leave.policies.store');
            Route::get('/leave/policies/{policy}/edit', [LeavePolicyController::class, 'edit'])->name('leave.policies.edit');
            Route::put('/leave/policies/{policy}', [LeavePolicyController::class, 'update'])->name('leave.policies.update');
            Route::delete('/leave/policies/{policy}', [LeavePolicyController::class, 'destroy'])->name('leave.policies.destroy');

            Route::get('/leave/rules/create', [LeaveRuleController::class, 'create'])->name('leave.rules.create');
            Route::post('/leave/rules', [LeaveRuleController::class, 'store'])->name('leave.rules.store');
            Route::get('/leave/rules/{rule}/edit', [LeaveRuleController::class, 'edit'])->name('leave.rules.edit');
            Route::put('/leave/rules/{rule}', [LeaveRuleController::class, 'update'])->name('leave.rules.update');
            Route::delete('/leave/rules/{rule}', [LeaveRuleController::class, 'destroy'])->name('leave.rules.destroy');

            Route::get('/leave/maternity-rules/create', [MaternityRuleController::class, 'create'])->name('leave.maternity-rules.create');
            Route::post('/leave/maternity-rules', [MaternityRuleController::class, 'store'])->name('leave.maternity-rules.store');
            Route::get('/leave/maternity-rules/{maternityRule}/edit', [MaternityRuleController::class, 'edit'])->name('leave.maternity-rules.edit');
            Route::put('/leave/maternity-rules/{maternityRule}', [MaternityRuleController::class, 'update'])->name('leave.maternity-rules.update');
            Route::delete('/leave/maternity-rules/{maternityRule}', [MaternityRuleController::class, 'destroy'])->name('leave.maternity-rules.destroy');

            Route::get('/leave/opening-balances/create', [OpeningBalanceController::class, 'create'])->name('leave.opening-balances.create');
            Route::post('/leave/opening-balances', [OpeningBalanceController::class, 'store'])->name('leave.opening-balances.store');
            Route::get('/leave/opening-balances/{openingBalance}/edit', [OpeningBalanceController::class, 'edit'])->name('leave.opening-balances.edit');
            Route::put('/leave/opening-balances/{openingBalance}', [OpeningBalanceController::class, 'update'])->name('leave.opening-balances.update');

            Route::post('/leave/allocation/run', [LeaveAllocationController::class, 'run'])->name('leave.allocation.run');

            Route::get('/leave/bulk-entry/template', [LeaveBulkEntryController::class, 'template'])->name('leave.bulk-entry.template');
            Route::post('/leave/bulk-entry', [LeaveBulkEntryController::class, 'store'])->name('leave.bulk-entry.store');

            Route::get('/leave/maternity-transactions/create', [MaternityTransactionController::class, 'create'])->name('leave.maternity-transactions.create');
            Route::post('/leave/maternity-transactions', [MaternityTransactionController::class, 'store'])->name('leave.maternity-transactions.store');
        });

        Route::middleware('permission:hrm.leave.approve')->group(function () {
            Route::post('/leave/transactions/{transaction}/approve', [LeaveTransactionController::class, 'approve'])->name('leave.transactions.approve');
            Route::post('/leave/transactions/{transaction}/reject', [LeaveTransactionController::class, 'reject'])->name('leave.transactions.reject');
        });

        // Legacy leave redirects
        Route::redirect('/leave/applications', '/admin/hrm/leave/transactions');
        Route::redirect('/leave/balances', '/admin/hrm/leave/opening-balances');

        // ── Performance sub-modules ──
        Route::middleware('permission:hrm.performance.view')->group(function () {
            Route::get('/performance', PerformanceHubController::class)->name('performance.hub');
            Route::get('/performance/dashboard', [\App\Http\Controllers\Admin\Hrm\Performance\DashboardController::class, 'index'])->name('performance.dashboard');

            Route::get('/performance/cycles', [PerformanceCycleController::class, 'index'])->name('performance.cycles.index');
            Route::get('/performance/cycles/{cycle}', [PerformanceCycleController::class, 'show'])->name('performance.cycles.show')->whereNumber('cycle');

            Route::get('/performance/templates', [PerformanceTemplateController::class, 'index'])->name('performance.templates.index');
            Route::get('/performance/templates/{template}', [PerformanceTemplateController::class, 'show'])->name('performance.templates.show')->whereNumber('template');

            Route::get('/performance/reviews', [PerformanceReviewController::class, 'index'])->name('performance.reviews.index');
            Route::get('/performance/reviews/export', [PerformanceReviewController::class, 'export'])->name('performance.reviews.export');
            Route::get('/performance/reviews/{review}', [PerformanceReviewController::class, 'show'])->name('performance.reviews.show')->whereNumber('review');
        });

        Route::middleware('permission:hrm.performance.rate')->group(function () {
            Route::post('/performance/reviews/{review}/rate', [PerformanceReviewController::class, 'rate'])->name('performance.reviews.rate')->whereNumber('review');
        });

        Route::middleware('permission:hrm.performance.approve')->group(function () {
            Route::post('/performance/reviews/{review}/approve', [PerformanceReviewController::class, 'approve'])->name('performance.reviews.approve')->whereNumber('review');
            Route::post('/performance/reviews/{review}/reject', [PerformanceReviewController::class, 'reject'])->name('performance.reviews.reject')->whereNumber('review');
        });

        Route::middleware('permission:hrm.performance.manage')->group(function () {
            Route::get('/performance/cycles/create', [PerformanceCycleController::class, 'create'])->name('performance.cycles.create');
            Route::post('/performance/cycles', [PerformanceCycleController::class, 'store'])->name('performance.cycles.store');
            Route::post('/performance/cycles/{cycle}/close', [PerformanceCycleController::class, 'close'])->name('performance.cycles.close')->whereNumber('cycle');

            Route::get('/performance/templates/create', [PerformanceTemplateController::class, 'create'])->name('performance.templates.create');
            Route::post('/performance/templates', [PerformanceTemplateController::class, 'store'])->name('performance.templates.store');
            Route::get('/performance/templates/{template}/edit', [PerformanceTemplateController::class, 'edit'])->name('performance.templates.edit')->whereNumber('template');
            Route::put('/performance/templates/{template}', [PerformanceTemplateController::class, 'update'])->name('performance.templates.update')->whereNumber('template');

            Route::post('/performance/reviews/{review}/assign-reporting', [PerformanceReviewController::class, 'assignReporting'])->name('performance.reviews.assign-reporting')->whereNumber('review');
            Route::post('/performance/reviews/{review}/recalculate', [PerformanceReviewController::class, 'recalculate'])->name('performance.reviews.recalculate')->whereNumber('review');
            Route::delete('/performance/reviews/{review}', [PerformanceReviewController::class, 'cancel'])->name('performance.reviews.cancel')->whereNumber('review');
        });

        Route::middleware('permission:hrm.performance.bonus.view')->group(function () {
            Route::get('/performance/bonus-bands', [PerformanceBonusBandController::class, 'index'])->name('performance.bonus-bands.index');

            Route::get('/performance/bonus-runs', [PerformanceBonusRunController::class, 'index'])->name('performance.bonus-runs.index');
            Route::get('/performance/bonus-runs/create', [PerformanceBonusRunController::class, 'create'])->name('performance.bonus-runs.create');
            Route::get('/performance/bonus-runs/{bonusRun}', [PerformanceBonusRunController::class, 'show'])->name('performance.bonus-runs.show')->whereNumber('bonusRun');
            Route::get('/performance/bonus-runs/{bonusRun}/export', [PerformanceBonusRunController::class, 'export'])->name('performance.bonus-runs.export')->whereNumber('bonusRun');
        });

        Route::middleware('permission:hrm.performance.bonus.manage')->group(function () {
            Route::get('/performance/bonus-bands/edit', [PerformanceBonusBandController::class, 'edit'])->name('performance.bonus-bands.edit');
            Route::put('/performance/bonus-bands', [PerformanceBonusBandController::class, 'update'])->name('performance.bonus-bands.update');
            Route::post('/performance/bonus-bands/reset', [PerformanceBonusBandController::class, 'reset'])->name('performance.bonus-bands.reset');

            Route::post('/performance/bonus-runs', [PerformanceBonusRunController::class, 'store'])->name('performance.bonus-runs.store');
            Route::post('/performance/bonus-runs/{bonusRun}/calculate', [PerformanceBonusRunController::class, 'calculate'])->name('performance.bonus-runs.calculate')->whereNumber('bonusRun');
            Route::post('/performance/bonus-runs/{bonusRun}/approve', [PerformanceBonusRunController::class, 'approve'])->name('performance.bonus-runs.approve')->whereNumber('bonusRun');
            Route::put('/performance/bonus-runs/{bonusRun}/items/{item}', [PerformanceBonusRunController::class, 'updateItem'])->name('performance.bonus-runs.items.update')->whereNumber(['bonusRun', 'item']);
        });

        Route::middleware('permission:hrm.performance.increment.view')->group(function () {
            Route::get('/performance/increment-bands', [PerformanceIncrementBandController::class, 'index'])->name('performance.increment-bands.index');

            Route::get('/performance/increment-runs', [PerformanceIncrementRunController::class, 'index'])->name('performance.increment-runs.index');
            Route::get('/performance/increment-runs/create', [PerformanceIncrementRunController::class, 'create'])->name('performance.increment-runs.create');
            Route::get('/performance/increment-runs/{incrementRun}', [PerformanceIncrementRunController::class, 'show'])->name('performance.increment-runs.show')->whereNumber('incrementRun');
            Route::get('/performance/increment-runs/{incrementRun}/export', [PerformanceIncrementRunController::class, 'export'])->name('performance.increment-runs.export')->whereNumber('incrementRun');
        });

        Route::middleware('permission:hrm.performance.increment.manage')->group(function () {
            Route::get('/performance/increment-bands/edit', [PerformanceIncrementBandController::class, 'edit'])->name('performance.increment-bands.edit');
            Route::put('/performance/increment-bands', [PerformanceIncrementBandController::class, 'update'])->name('performance.increment-bands.update');
            Route::post('/performance/increment-bands/reset', [PerformanceIncrementBandController::class, 'reset'])->name('performance.increment-bands.reset');

            Route::post('/performance/increment-runs', [PerformanceIncrementRunController::class, 'store'])->name('performance.increment-runs.store');
            Route::post('/performance/increment-runs/{incrementRun}/calculate', [PerformanceIncrementRunController::class, 'calculate'])->name('performance.increment-runs.calculate')->whereNumber('incrementRun');
            Route::post('/performance/increment-runs/{incrementRun}/apply', [PerformanceIncrementRunController::class, 'apply'])->name('performance.increment-runs.apply')->whereNumber('incrementRun');
            Route::put('/performance/increment-runs/{incrementRun}/items/{item}', [PerformanceIncrementRunController::class, 'updateItem'])->name('performance.increment-runs.items.update')->whereNumber(['incrementRun', 'item']);
        });

        // ── Salary sub-modules ──
        Route::middleware('permission:hrm.salary.view')->group(function () {
            Route::get('/salary', SalaryHubController::class)->name('salary.hub');
            Route::get('/salary/dashboard', [\App\Http\Controllers\Admin\Hrm\Salary\DashboardController::class, 'index'])->name('salary.dashboard');
            Route::get('/salary/planned/{module}', SalaryPlannedController::class)->name('salary.planned');

            Route::get('/salary/heads', [SalaryHeadController::class, 'index'])->name('salary.heads.index');
            Route::get('/salary/heads/{head}', [SalaryHeadController::class, 'show'])->name('salary.heads.show')->whereNumber('head');
            Route::get('/salary/grades', [SalaryGradeController::class, 'index'])->name('salary.grades.index');
            Route::get('/salary/grades/{grade}', [SalaryGradeController::class, 'show'])->name('salary.grades.show')->whereNumber('grade');
            Route::get('/salary/grade-details', [GradeDetailController::class, 'index'])->name('salary.grade-details.index');
            Route::get('/salary/grade-details/{gradeDetail}', [GradeDetailController::class, 'show'])->name('salary.grade-details.show')->whereNumber('gradeDetail');
            Route::get('/salary/employee-salary', [EmployeeSalaryController::class, 'index'])->name('salary.employee-salary.index');
            Route::get('/salary/upload', [SalaryUploadController::class, 'index'])->name('salary.upload.index');
            Route::get('/salary/process', [SalaryProcessController::class, 'index'])->name('salary.process.index');
            Route::get('/salary/process/{period}', [SalaryProcessController::class, 'show'])->name('salary.process.show');
            Route::get('/salary/process/{period}/payslip/{item}', [SalaryProcessController::class, 'payslip'])->name('salary.process.payslip')->whereNumber('item');
            Route::get('/salary/process/{period}/payslip/{item}/print', [SalaryProcessController::class, 'payslipPrint'])->name('salary.process.payslip.print')->whereNumber('item');
            Route::get('/salary/close', [SalaryCloseController::class, 'index'])->name('salary.close.index');
            Route::get('/salary/increment-rules', [IncrementRuleController::class, 'index'])->name('salary.increment-rules.index');
            Route::get('/salary/increment-bulk', [IncrementBulkController::class, 'index'])->name('salary.increment-bulk.index');
            Route::get('/salary/increment-upload', [IncrementUploadController::class, 'index'])->name('salary.increment-upload.index');
        });

        Route::middleware('permission:hrm.salary.manage')->group(function () {
            Route::get('/salary/heads/create', [SalaryHeadController::class, 'create'])->name('salary.heads.create');
            Route::post('/salary/heads', [SalaryHeadController::class, 'store'])->name('salary.heads.store');
            Route::get('/salary/heads/{head}/edit', [SalaryHeadController::class, 'edit'])->name('salary.heads.edit');
            Route::put('/salary/heads/{head}', [SalaryHeadController::class, 'update'])->name('salary.heads.update');
            Route::delete('/salary/heads/{head}', [SalaryHeadController::class, 'destroy'])->name('salary.heads.destroy');

            Route::get('/salary/grades/create', [SalaryGradeController::class, 'create'])->name('salary.grades.create');
            Route::post('/salary/grades', [SalaryGradeController::class, 'store'])->name('salary.grades.store');
            Route::get('/salary/grades/{grade}/edit', [SalaryGradeController::class, 'edit'])->name('salary.grades.edit');
            Route::put('/salary/grades/{grade}', [SalaryGradeController::class, 'update'])->name('salary.grades.update');
            Route::delete('/salary/grades/{grade}', [SalaryGradeController::class, 'destroy'])->name('salary.grades.destroy');

            Route::get('/salary/grade-details/create', [GradeDetailController::class, 'create'])->name('salary.grade-details.create');
            Route::post('/salary/grade-details', [GradeDetailController::class, 'store'])->name('salary.grade-details.store');
            Route::get('/salary/grade-details/{gradeDetail}/edit', [GradeDetailController::class, 'edit'])->name('salary.grade-details.edit');
            Route::put('/salary/grade-details/{gradeDetail}', [GradeDetailController::class, 'update'])->name('salary.grade-details.update');
            Route::delete('/salary/grade-details/{gradeDetail}', [GradeDetailController::class, 'destroy'])->name('salary.grade-details.destroy');

            Route::get('/salary/employee-salary/create', [EmployeeSalaryController::class, 'create'])->name('salary.employee-salary.create');
            Route::post('/salary/employee-salary/calculate', [EmployeeSalaryController::class, 'calculate'])->name('salary.employee-salary.calculate');
            Route::post('/salary/employee-salary', [EmployeeSalaryController::class, 'store'])->name('salary.employee-salary.store');
            Route::get('/salary/employee-salary/{salaryStructure}/edit', [EmployeeSalaryController::class, 'edit'])->name('salary.employee-salary.edit');
            Route::put('/salary/employee-salary/{salaryStructure}', [EmployeeSalaryController::class, 'update'])->name('salary.employee-salary.update');

            Route::get('/salary/upload/template', [SalaryUploadController::class, 'template'])->name('salary.upload.template');
            Route::post('/salary/upload', [SalaryUploadController::class, 'store'])->name('salary.upload.store');

            Route::post('/salary/process/run', [SalaryProcessController::class, 'run'])->name('salary.process.run');

            Route::get('/salary/increment-rules/create', [IncrementRuleController::class, 'create'])->name('salary.increment-rules.create');
            Route::post('/salary/increment-rules', [IncrementRuleController::class, 'store'])->name('salary.increment-rules.store');
            Route::get('/salary/increment-rules/{incrementRule}/edit', [IncrementRuleController::class, 'edit'])->name('salary.increment-rules.edit');
            Route::put('/salary/increment-rules/{incrementRule}', [IncrementRuleController::class, 'update'])->name('salary.increment-rules.update');
            Route::delete('/salary/increment-rules/{incrementRule}', [IncrementRuleController::class, 'destroy'])->name('salary.increment-rules.destroy');

            Route::post('/salary/increment-bulk/apply', [IncrementBulkController::class, 'apply'])->name('salary.increment-bulk.apply');

            Route::get('/salary/increment-upload/template', [IncrementUploadController::class, 'template'])->name('salary.increment-upload.template');
            Route::post('/salary/increment-upload', [IncrementUploadController::class, 'store'])->name('salary.increment-upload.store');
        });

        Route::middleware('permission:hrm.salary.approve')->group(function () {
            Route::post('/salary/close/{period}/freeze', [SalaryCloseController::class, 'freeze'])->name('salary.close.freeze');
            Route::post('/salary/close/{period}/send-payslips', [SalaryCloseController::class, 'sendPayslips'])->name('salary.close.send-payslips');
            Route::get('/salary/close/{period}/bank-advise', [SalaryCloseController::class, 'bankAdvise'])->name('salary.close.bank-advise');
        });

        // ── Compliance sub-modules ──
        Route::middleware('permission:hrm.compliance.view')->group(function () {
            Route::get('/compliance', ComplianceHubController::class)->name('compliance.hub');
            Route::get('/compliance/dashboard', [\App\Http\Controllers\Admin\Hrm\Compliance\DashboardController::class, 'index'])->name('compliance.dashboard');
            Route::get('/compliance/registers', [ComplianceRegisterController::class, 'index'])->name('compliance.registers.index');
            Route::get('/compliance/registers/export/{type}', [ComplianceRegisterController::class, 'export'])->name('compliance.registers.export');
            Route::get('/compliance/bonus', [ComplianceBonusController::class, 'index'])->name('compliance.bonus.index');
            Route::get('/compliance/bonus/{bonusRun}', [ComplianceBonusController::class, 'show'])->name('compliance.bonus.show')->whereNumber('bonusRun');
            Route::get('/compliance/bonus/{bonusRun}/export', [ComplianceBonusController::class, 'export'])->name('compliance.bonus.export')->whereNumber('bonusRun');
            Route::get('/compliance/gratuity', [ComplianceGratuityController::class, 'index'])->name('compliance.gratuity.index');
            Route::get('/compliance/gratuity/{gratuitySettlement}', [ComplianceGratuityController::class, 'show'])->name('compliance.gratuity.show')->whereNumber('gratuitySettlement');
            Route::get('/compliance/gratuity-export', [ComplianceGratuityController::class, 'export'])->name('compliance.gratuity.export');
            Route::get('/compliance/age-verification', [ComplianceAgeVerificationController::class, 'index'])->name('compliance.age-verification.index');
            Route::get('/compliance/age-verification/export', [ComplianceAgeVerificationController::class, 'export'])->name('compliance.age-verification.export');
            Route::get('/compliance/working-hours', [ComplianceWorkingHoursController::class, 'index'])->name('compliance.working-hours.index');
        });

        Route::middleware('permission:hrm.compliance.manage')->group(function () {
            Route::get('/compliance/bonus/create', [ComplianceBonusController::class, 'create'])->name('compliance.bonus.create');
            Route::post('/compliance/bonus', [ComplianceBonusController::class, 'store'])->name('compliance.bonus.store');
            Route::post('/compliance/bonus/{bonusRun}/calculate', [ComplianceBonusController::class, 'calculate'])->name('compliance.bonus.calculate')->whereNumber('bonusRun');
            Route::post('/compliance/bonus/{bonusRun}/approve', [ComplianceBonusController::class, 'approve'])->name('compliance.bonus.approve')->whereNumber('bonusRun');
            Route::post('/compliance/gratuity/{gratuitySettlement}/paid', [ComplianceGratuityController::class, 'markPaid'])->name('compliance.gratuity.paid')->whereNumber('gratuitySettlement');
            Route::post('/compliance/working-hours/notify', [ComplianceWorkingHoursController::class, 'notify'])->name('compliance.working-hours.notify');
        });

        Route::middleware('permission:hrm.finance.view')->group(function () {
            Route::get('/finance', FinanceHubController::class)->name('finance.hub');
            Route::get('/finance/dashboard', [\App\Http\Controllers\Admin\Hrm\Finance\DashboardController::class, 'index'])->name('finance.dashboard');
            Route::get('/finance/tax', [FinanceTaxController::class, 'index'])->name('finance.tax.index');
            Route::get('/finance/tax/certificate', [FinanceTaxController::class, 'certificate'])->name('finance.tax.certificate');
            Route::get('/finance/tax/export-annual', [FinanceTaxController::class, 'exportAnnualTds'])->name('finance.tax.export-annual');
            Route::get('/finance/pf', [FinancePfController::class, 'index'])->name('finance.pf.index');
            Route::get('/finance/pf/{account}', [FinancePfController::class, 'show'])->name('finance.pf.show')->whereNumber('account');
            Route::get('/finance/pf/employer-report', [FinancePfController::class, 'employerReport'])->name('finance.pf.employer-report');
            Route::get('/finance/pf/employer-report/export', [FinancePfController::class, 'exportEmployerReport'])->name('finance.pf.employer-report.export');
            Route::get('/finance/loans', [FinanceLoanController::class, 'index'])->name('finance.loans.index');
            Route::get('/finance/loans/bulk', [FinanceBulkAdvanceController::class, 'index'])->name('finance.loans.bulk');
            Route::get('/finance/loans/{loan}/statement', [FinanceLoanController::class, 'statement'])->name('finance.loans.statement')->whereNumber('loan');
            Route::get('/finance/loans/{loan}', [FinanceLoanController::class, 'show'])->name('finance.loans.show')->whereNumber('loan');
        });

        Route::middleware('permission:hrm.finance.settlement.view')->group(function () {
            Route::get('/finance/final-settlement', [FinanceFinalSettlementController::class, 'index'])->name('finance.final-settlement.index');
            Route::get('/finance/final-settlement/export', [FinanceFinalSettlementController::class, 'export'])->name('finance.final-settlement.export');
            Route::get('/finance/final-settlement/{finalSettlement}', [FinanceFinalSettlementController::class, 'show'])->name('finance.final-settlement.show')->whereNumber('finalSettlement');
            Route::get('/finance/final-settlement/{finalSettlement}/print', [FinanceFinalSettlementController::class, 'print'])->name('finance.final-settlement.print')->whereNumber('finalSettlement');
        });

        Route::middleware('permission:hrm.finance.settlement.manage')->group(function () {
            Route::get('/finance/final-settlement/create', [FinanceFinalSettlementController::class, 'create'])->name('finance.final-settlement.create');
            Route::post('/finance/final-settlement', [FinanceFinalSettlementController::class, 'store'])->name('finance.final-settlement.store');
            Route::post('/finance/final-settlement/{finalSettlement}/calculate', [FinanceFinalSettlementController::class, 'calculate'])->name('finance.final-settlement.calculate')->whereNumber('finalSettlement');
            Route::put('/finance/final-settlement/{finalSettlement}/adjustments', [FinanceFinalSettlementController::class, 'updateAdjustments'])->name('finance.final-settlement.adjustments')->whereNumber('finalSettlement');
            Route::put('/finance/final-settlement/{finalSettlement}/clearance', [FinanceFinalSettlementController::class, 'updateClearance'])->name('finance.final-settlement.clearance')->whereNumber('finalSettlement');
            Route::post('/finance/final-settlement/{finalSettlement}/approve', [FinanceFinalSettlementController::class, 'approve'])->name('finance.final-settlement.approve')->whereNumber('finalSettlement');
            Route::post('/finance/final-settlement/{finalSettlement}/paid', [FinanceFinalSettlementController::class, 'markPaid'])->name('finance.final-settlement.paid')->whereNumber('finalSettlement');
        });

        Route::middleware('permission:hrm.finance.manage')->group(function () {
            Route::get('/finance/tax/create', [FinanceTaxController::class, 'create'])->name('finance.tax.create');
            Route::post('/finance/tax', [FinanceTaxController::class, 'store'])->name('finance.tax.store');
            Route::get('/finance/tax/{taxYear}/edit', [FinanceTaxController::class, 'edit'])->name('finance.tax.edit')->whereNumber('taxYear');
            Route::put('/finance/tax/{taxYear}', [FinanceTaxController::class, 'update'])->name('finance.tax.update')->whereNumber('taxYear');
            Route::get('/finance/pf/create', [FinancePfController::class, 'create'])->name('finance.pf.create');
            Route::post('/finance/pf', [FinancePfController::class, 'store'])->name('finance.pf.store');
            Route::get('/finance/loans/create', [FinanceLoanController::class, 'create'])->name('finance.loans.create');
            Route::post('/finance/loans', [FinanceLoanController::class, 'store'])->name('finance.loans.store');
            Route::post('/finance/loans/bulk', [FinanceBulkAdvanceController::class, 'store'])->name('finance.loans.bulk.store');
            Route::post('/finance/loans/{loan}/approve', [FinanceLoanController::class, 'approve'])->name('finance.loans.approve')->whereNumber('loan');
            Route::post('/finance/loans/{loan}/settle', [FinanceLoanController::class, 'settle'])->name('finance.loans.settle')->whereNumber('loan');
            Route::post('/finance/loans/{loan}/reject', [FinanceLoanController::class, 'reject'])->name('finance.loans.reject')->whereNumber('loan');
        });

        // ── RMG extras ──
        Route::middleware('permission:hrm.rmg.view')->group(function () {
            Route::get('/rmg', RmgHubController::class)->name('rmg.hub');
            Route::get('/rmg/dashboard', [\App\Http\Controllers\Admin\Hrm\Rmg\DashboardController::class, 'index'])->name('rmg.dashboard');
            Route::get('/rmg/worker-transfer', [RmgWorkerTransferController::class, 'index'])->name('rmg.worker-transfer.index');
            Route::get('/rmg/gate-pass', [RmgGatePassController::class, 'index'])->name('rmg.gate-pass.index');
            Route::get('/rmg/manpower-planning', [RmgManpowerPlanningController::class, 'index'])->name('rmg.manpower-planning.index');
            Route::get('/rmg/proxy-punch', [RmgProxyPunchController::class, 'index'])->name('rmg.proxy-punch.index');

            foreach (['osd-movement', 'canteen', 'medical', 'training', 'sub-contract', 'buyer-holiday', 'salary-hold', 'production-incentive'] as $rmgSub) {
                Route::get('/rmg/' . $rmgSub, [GenericRmgController::class, 'index'])
                    ->defaults('submodule', $rmgSub)
                    ->name('rmg.' . $rmgSub . '.index');
            }

            Route::get('/rmg/cash-list', [RmgExportController::class, 'cashListIndex'])->name('rmg.cash-list.index');
            Route::get('/rmg/cash-list/export', [RmgExportController::class, 'cashListExport'])->name('rmg.cash-list.export');
            Route::get('/rmg/buyer-audit-export', [RmgExportController::class, 'buyerAuditIndex'])->name('rmg.buyer-audit-export.index');
            Route::get('/rmg/buyer-audit-export/export', [RmgExportController::class, 'buyerAuditExport'])->name('rmg.buyer-audit-export.export');
        });

        Route::middleware('permission:hrm.rmg.manage')->group(function () {
            Route::get('/rmg/worker-transfer/create', [RmgWorkerTransferController::class, 'create'])->name('rmg.worker-transfer.create');
            Route::post('/rmg/worker-transfer', [RmgWorkerTransferController::class, 'store'])->name('rmg.worker-transfer.store');
            Route::post('/rmg/worker-transfer/{workerTransfer}/approve', [RmgWorkerTransferController::class, 'approve'])
                ->name('rmg.worker-transfer.approve')->whereNumber('workerTransfer');
            Route::post('/rmg/worker-transfer/{workerTransfer}/reject', [RmgWorkerTransferController::class, 'reject'])
                ->name('rmg.worker-transfer.reject')->whereNumber('workerTransfer');

            Route::get('/rmg/gate-pass/create', [RmgGatePassController::class, 'create'])->name('rmg.gate-pass.create');
            Route::post('/rmg/gate-pass', [RmgGatePassController::class, 'store'])->name('rmg.gate-pass.store');
            Route::post('/rmg/gate-pass/{gatePass}/approve', [RmgGatePassController::class, 'approve'])
                ->name('rmg.gate-pass.approve')->whereNumber('gatePass');
            Route::post('/rmg/gate-pass/{gatePass}/reject', [RmgGatePassController::class, 'reject'])
                ->name('rmg.gate-pass.reject')->whereNumber('gatePass');

            Route::get('/rmg/manpower-planning/create', [RmgManpowerPlanningController::class, 'create'])->name('rmg.manpower-planning.create');
            Route::post('/rmg/manpower-planning', [RmgManpowerPlanningController::class, 'store'])->name('rmg.manpower-planning.store');

            Route::get('/rmg/proxy-punch/create', [RmgProxyPunchController::class, 'create'])->name('rmg.proxy-punch.create');
            Route::post('/rmg/proxy-punch', [RmgProxyPunchController::class, 'store'])->name('rmg.proxy-punch.store');
            Route::post('/rmg/proxy-punch/{proxyPunchFlag}/review', [RmgProxyPunchController::class, 'review'])
                ->name('rmg.proxy-punch.review')->whereNumber('proxyPunchFlag');

            foreach (['osd-movement', 'canteen', 'medical', 'training', 'sub-contract', 'buyer-holiday', 'salary-hold', 'production-incentive'] as $rmgSub) {
                Route::get('/rmg/' . $rmgSub . '/create', [GenericRmgController::class, 'create'])
                    ->defaults('submodule', $rmgSub)
                    ->name('rmg.' . $rmgSub . '.create');
                Route::post('/rmg/' . $rmgSub, [GenericRmgController::class, 'store'])
                    ->defaults('submodule', $rmgSub)
                    ->name('rmg.' . $rmgSub . '.store');
                Route::get('/rmg/' . $rmgSub . '/{record}/edit', [GenericRmgController::class, 'edit'])
                    ->defaults('submodule', $rmgSub)
                    ->name('rmg.' . $rmgSub . '.edit')
                    ->whereNumber('record');
                Route::put('/rmg/' . $rmgSub . '/{record}', [GenericRmgController::class, 'update'])
                    ->defaults('submodule', $rmgSub)
                    ->name('rmg.' . $rmgSub . '.update')
                    ->whereNumber('record');
                Route::delete('/rmg/' . $rmgSub . '/{record}', [GenericRmgController::class, 'destroy'])
                    ->defaults('submodule', $rmgSub)
                    ->name('rmg.' . $rmgSub . '.destroy')
                    ->whereNumber('record');
            }

            Route::post('/rmg/salary-hold/{salaryHold}/release', [GenericRmgController::class, 'release'])
                ->name('rmg.salary-hold.release')->whereNumber('salaryHold');
            Route::post('/rmg/production-incentive/{productionIncentive}/approve', [GenericRmgController::class, 'approveIncentive'])
                ->name('rmg.production-incentive.approve')->whereNumber('productionIncentive');
            Route::post('/rmg/osd-movement/{osdMovement}/approve', [GenericRmgController::class, 'approveOsd'])
                ->name('rmg.osd-movement.approve')->whereNumber('osdMovement');
            Route::post('/rmg/osd-movement/{osdMovement}/reject', [GenericRmgController::class, 'rejectOsd'])
                ->name('rmg.osd-movement.reject')->whereNumber('osdMovement');
        });

        // Legacy payroll redirects
        Route::redirect('/payroll', '/admin/hrm/salary');
        Route::redirect('/payroll/periods', '/admin/hrm/salary/process');
        Route::redirect('/payroll/salary-structures', '/admin/hrm/salary/employee-salary');
    });

    Route::prefix('tms')->name('tms.')->middleware('factory.scope')->group(function () {
        Route::middleware('tms.any')->group(function () {
            Route::get('/hub', \App\Http\Controllers\Admin\Tms\HubController::class)->name('hub');
            Route::get('/', [\App\Http\Controllers\Admin\Tms\DashboardController::class, 'index'])->name('dashboard');
        });

        Route::middleware('permission:tms.settings.view')->group(function () {
            Route::get('/settings', [\App\Http\Controllers\Admin\Tms\SettingsController::class, 'index'])->name('settings.index');
            Route::get('/destinations', [\App\Http\Controllers\Admin\Tms\DestinationController::class, 'index'])->name('destinations.index');
            Route::get('/gps', [\App\Http\Controllers\Admin\Tms\GpsController::class, 'index'])->name('gps.index');
        });

        Route::middleware('permission:tms.settings.manage')->group(function () {
            Route::put('/settings', [\App\Http\Controllers\Admin\Tms\SettingsController::class, 'update'])->name('settings.update');
            Route::get('/destinations/create', [\App\Http\Controllers\Admin\Tms\DestinationController::class, 'create'])->name('destinations.create');
            Route::post('/destinations', [\App\Http\Controllers\Admin\Tms\DestinationController::class, 'store'])->name('destinations.store');
            Route::get('/destinations/{destination}/edit', [\App\Http\Controllers\Admin\Tms\DestinationController::class, 'edit'])->name('destinations.edit')->whereNumber('destination');
            Route::put('/destinations/{destination}', [\App\Http\Controllers\Admin\Tms\DestinationController::class, 'update'])->name('destinations.update')->whereNumber('destination');
            Route::delete('/destinations/{destination}', [\App\Http\Controllers\Admin\Tms\DestinationController::class, 'destroy'])->name('destinations.destroy')->whereNumber('destination');
        });

        Route::middleware('permission:tms.vehicles.view')->group(function () {
            Route::get('/vehicles', [\App\Http\Controllers\Admin\Tms\VehicleController::class, 'index'])->name('vehicles.index');
            Route::get('/vehicles/{vehicle}', [\App\Http\Controllers\Admin\Tms\VehicleController::class, 'show'])->name('vehicles.show')->whereNumber('vehicle');
        });

        Route::middleware('permission:tms.vehicles.manage')->group(function () {
            Route::get('/vehicles/create', [\App\Http\Controllers\Admin\Tms\VehicleController::class, 'create'])->name('vehicles.create');
            Route::post('/vehicles', [\App\Http\Controllers\Admin\Tms\VehicleController::class, 'store'])->name('vehicles.store');
            Route::get('/vehicles/{vehicle}/edit', [\App\Http\Controllers\Admin\Tms\VehicleController::class, 'edit'])->name('vehicles.edit')->whereNumber('vehicle');
            Route::put('/vehicles/{vehicle}', [\App\Http\Controllers\Admin\Tms\VehicleController::class, 'update'])->name('vehicles.update')->whereNumber('vehicle');
            Route::delete('/vehicles/{vehicle}', [\App\Http\Controllers\Admin\Tms\VehicleController::class, 'destroy'])->name('vehicles.destroy')->whereNumber('vehicle');
        });

        Route::middleware('permission:tms.rental_vendors.view')->group(function () {
            Route::get('/rental-vendors', [\App\Http\Controllers\Admin\Tms\RentalVendorController::class, 'index'])->name('rental-vendors.index');
        });

        Route::middleware('permission:tms.rental_vendors.manage')->group(function () {
            Route::get('/rental-vendors/create', [\App\Http\Controllers\Admin\Tms\RentalVendorController::class, 'create'])->name('rental-vendors.create');
            Route::post('/rental-vendors', [\App\Http\Controllers\Admin\Tms\RentalVendorController::class, 'store'])->name('rental-vendors.store');
            Route::get('/rental-vendors/{rentalVendor}/edit', [\App\Http\Controllers\Admin\Tms\RentalVendorController::class, 'edit'])->name('rental-vendors.edit')->whereNumber('rentalVendor');
            Route::put('/rental-vendors/{rentalVendor}', [\App\Http\Controllers\Admin\Tms\RentalVendorController::class, 'update'])->name('rental-vendors.update')->whereNumber('rentalVendor');
            Route::delete('/rental-vendors/{rentalVendor}', [\App\Http\Controllers\Admin\Tms\RentalVendorController::class, 'destroy'])->name('rental-vendors.destroy')->whereNumber('rentalVendor');
        });

        Route::middleware('permission:tms.rental_drivers.view')->group(function () {
            Route::get('/rental-drivers', [\App\Http\Controllers\Admin\Tms\RentalDriverController::class, 'index'])->name('rental-drivers.index');
            Route::get('/rental-drivers/{rentalDriver}', [\App\Http\Controllers\Admin\Tms\RentalDriverController::class, 'show'])->name('rental-drivers.show')->whereNumber('rentalDriver');
        });

        Route::middleware('permission:tms.rental_drivers.manage')->group(function () {
            Route::get('/rental-drivers/create', [\App\Http\Controllers\Admin\Tms\RentalDriverController::class, 'create'])->name('rental-drivers.create');
            Route::post('/rental-drivers', [\App\Http\Controllers\Admin\Tms\RentalDriverController::class, 'store'])->name('rental-drivers.store');
            Route::get('/rental-drivers/{rentalDriver}/edit', [\App\Http\Controllers\Admin\Tms\RentalDriverController::class, 'edit'])->name('rental-drivers.edit')->whereNumber('rentalDriver');
            Route::put('/rental-drivers/{rentalDriver}', [\App\Http\Controllers\Admin\Tms\RentalDriverController::class, 'update'])->name('rental-drivers.update')->whereNumber('rentalDriver');
            Route::delete('/rental-drivers/{rentalDriver}', [\App\Http\Controllers\Admin\Tms\RentalDriverController::class, 'destroy'])->name('rental-drivers.destroy')->whereNumber('rentalDriver');
        });

        Route::middleware('permission:tms.drivers.view')->group(function () {
            Route::get('/drivers', [\App\Http\Controllers\Admin\Tms\DriverController::class, 'index'])->name('drivers.index');
            Route::get('/drivers/{driver}', [\App\Http\Controllers\Admin\Tms\DriverController::class, 'show'])->name('drivers.show')->whereNumber('driver');
        });

        Route::middleware('permission:tms.drivers.manage')->group(function () {
            Route::get('/drivers/create', [\App\Http\Controllers\Admin\Tms\DriverController::class, 'create'])->name('drivers.create');
            Route::post('/drivers', [\App\Http\Controllers\Admin\Tms\DriverController::class, 'store'])->name('drivers.store');
            Route::get('/drivers/{driver}/edit', [\App\Http\Controllers\Admin\Tms\DriverController::class, 'edit'])->name('drivers.edit')->whereNumber('driver');
            Route::put('/drivers/{driver}', [\App\Http\Controllers\Admin\Tms\DriverController::class, 'update'])->name('drivers.update')->whereNumber('driver');
            Route::delete('/drivers/{driver}', [\App\Http\Controllers\Admin\Tms\DriverController::class, 'destroy'])->name('drivers.destroy')->whereNumber('driver');
        });

        Route::middleware('permission:tms.requests.view')->group(function () {
            Route::get('/requests', [\App\Http\Controllers\Admin\Tms\RequestController::class, 'index'])->name('requests.index');
            Route::get('/requests/{transportRequest}', [\App\Http\Controllers\Admin\Tms\RequestController::class, 'show'])->name('requests.show')->whereNumber('transportRequest');
        });

        Route::middleware('permission:tms.requests.approve')->group(function () {
            Route::post('/requests/merge', [\App\Http\Controllers\Admin\Tms\RequestController::class, 'merge'])->name('requests.merge');
            Route::post('/requests/{transportRequest}/approve', [\App\Http\Controllers\Admin\Tms\RequestController::class, 'approve'])->name('requests.approve')->whereNumber('transportRequest');
            Route::post('/requests/{transportRequest}/reject', [\App\Http\Controllers\Admin\Tms\RequestController::class, 'reject'])->name('requests.reject')->whereNumber('transportRequest');
            Route::post('/requests/{transportRequest}/cancel', [\App\Http\Controllers\Admin\Tms\RequestController::class, 'cancel'])->name('requests.cancel')->whereNumber('transportRequest');
            Route::post('/requests/{transportRequest}/reassign', [\App\Http\Controllers\Admin\Tms\RequestController::class, 'reassign'])->name('requests.reassign')->whereNumber('transportRequest');
        });

        Route::middleware('permission:tms.trips.view')->group(function () {
            Route::get('/trips', [\App\Http\Controllers\Admin\Tms\TripController::class, 'index'])->name('trips.index');
            Route::get('/trips/{trip}', [\App\Http\Controllers\Admin\Tms\TripController::class, 'show'])->name('trips.show')->whereNumber('trip');
            Route::get('/odometer', [\App\Http\Controllers\Admin\Tms\OdometerController::class, 'index'])->name('odometer.index');
        });

        Route::middleware('permission:tms.trips.manage')->group(function () {
            Route::post('/trips/{trip}/start', [\App\Http\Controllers\Admin\Tms\TripController::class, 'start'])->name('trips.start')->whereNumber('trip');
            Route::post('/trips/{trip}/end', [\App\Http\Controllers\Admin\Tms\TripController::class, 'end'])->name('trips.end')->whereNumber('trip');
            Route::post('/trips/{trip}/abort', [\App\Http\Controllers\Admin\Tms\TripController::class, 'abort'])->name('trips.abort')->whereNumber('trip');
            Route::get('/odometer/morning/create', [\App\Http\Controllers\Admin\Tms\OdometerController::class, 'createMorning'])->name('odometer.morning.create');
            Route::post('/odometer/morning', [\App\Http\Controllers\Admin\Tms\OdometerController::class, 'storeMorning'])->name('odometer.morning.store');
            Route::get('/odometer/{odometer}/evening', [\App\Http\Controllers\Admin\Tms\OdometerController::class, 'createEvening'])->name('odometer.evening.create')->whereNumber('odometer');
            Route::post('/odometer/{odometer}/evening', [\App\Http\Controllers\Admin\Tms\OdometerController::class, 'storeEvening'])->name('odometer.evening.store')->whereNumber('odometer');
            Route::get('/odometer/{odometer}/edit', [\App\Http\Controllers\Admin\Tms\OdometerController::class, 'edit'])->name('odometer.edit')->whereNumber('odometer');
            Route::put('/odometer/{odometer}', [\App\Http\Controllers\Admin\Tms\OdometerController::class, 'update'])->name('odometer.update')->whereNumber('odometer');
            Route::delete('/odometer/{odometer}', [\App\Http\Controllers\Admin\Tms\OdometerController::class, 'destroy'])->name('odometer.destroy')->whereNumber('odometer');
        });

        Route::middleware('permission:tms.overtime.manage')->group(function () {
            Route::post('/trips/{trip}/mark-ot-paid', [\App\Http\Controllers\Admin\Tms\TripController::class, 'markOtPaid'])->name('trips.mark-ot-paid')->whereNumber('trip');
            Route::post('/trips/{trip}/unmark-ot-paid', [\App\Http\Controllers\Admin\Tms\TripController::class, 'unmarkOtPaid'])->name('trips.unmark-ot-paid')->whereNumber('trip');
        });

        Route::middleware('permission:tms.rental_charges.manage')->group(function () {
            Route::get('/rental-charges', [\App\Http\Controllers\Admin\Tms\RentalChargeController::class, 'index'])->name('rental-charges.index');
            Route::post('/trips/{trip}/mark-rental-paid', [\App\Http\Controllers\Admin\Tms\TripController::class, 'markRentalChargePaid'])->name('trips.mark-rental-paid')->whereNumber('trip');
            Route::post('/trips/{trip}/unmark-rental-paid', [\App\Http\Controllers\Admin\Tms\TripController::class, 'unmarkRentalChargePaid'])->name('trips.unmark-rental-paid')->whereNumber('trip');
            Route::post('/rental-charges/{charge}/mark-paid', [\App\Http\Controllers\Admin\Tms\RentalChargeController::class, 'markPaid'])->name('rental-charges.mark-paid')->whereNumber('charge');
            Route::post('/rental-charges/{charge}/unmark-paid', [\App\Http\Controllers\Admin\Tms\RentalChargeController::class, 'markUnpaid'])->name('rental-charges.unmark-paid')->whereNumber('charge');
        });

        Route::middleware('permission:tms.fuel.view')->group(function () {
            Route::get('/fuel', [\App\Http\Controllers\Admin\Tms\FuelController::class, 'index'])->name('fuel.index');
            Route::get('/fuel/{fuelLog}', [\App\Http\Controllers\Admin\Tms\FuelController::class, 'show'])->name('fuel.show')->whereNumber('fuelLog');
            Route::get('/fuel/{fuelLog}/receipt', [\App\Http\Controllers\Admin\Tms\FuelController::class, 'downloadReceipt'])->name('fuel.receipt')->whereNumber('fuelLog');
        });

        Route::middleware('permission:tms.fuel.manage')->group(function () {
            Route::get('/fuel/create', [\App\Http\Controllers\Admin\Tms\FuelController::class, 'create'])->name('fuel.create');
            Route::post('/fuel', [\App\Http\Controllers\Admin\Tms\FuelController::class, 'store'])->name('fuel.store');
            Route::get('/fuel/{fuelLog}/edit', [\App\Http\Controllers\Admin\Tms\FuelController::class, 'edit'])->name('fuel.edit')->whereNumber('fuelLog');
            Route::put('/fuel/{fuelLog}', [\App\Http\Controllers\Admin\Tms\FuelController::class, 'update'])->name('fuel.update')->whereNumber('fuelLog');
            Route::delete('/fuel/{fuelLog}', [\App\Http\Controllers\Admin\Tms\FuelController::class, 'destroy'])->name('fuel.destroy')->whereNumber('fuelLog');
        });

        Route::middleware('permission:tms.maintenance.view')->group(function () {
            Route::get('/maintenance', [\App\Http\Controllers\Admin\Tms\MaintenanceController::class, 'index'])->name('maintenance.index');
            Route::get('/maintenance/vehicles/{vehicle}/register', [\App\Http\Controllers\Admin\Tms\MaintenanceController::class, 'register'])->name('maintenance.register')->whereNumber('vehicle');
            Route::get('/maintenance/vehicles/{vehicle}/register/print', [\App\Http\Controllers\Admin\Tms\MaintenanceController::class, 'printRegister'])->name('maintenance.register.print')->whereNumber('vehicle');
            Route::get('/maintenance/posting', [\App\Http\Controllers\Admin\Tms\MaintenancePostingController::class, 'index'])->name('maintenance.posting');
            Route::get('/maintenance/posting/print', [\App\Http\Controllers\Admin\Tms\MaintenancePostingController::class, 'print'])->name('maintenance.posting.print');
            Route::get('/maintenance/posting/export', [\App\Http\Controllers\Admin\Tms\MaintenancePostingController::class, 'export'])->name('maintenance.posting.export');
            Route::get('/maintenance/parts', [\App\Http\Controllers\Admin\Tms\MaintenancePartController::class, 'index'])->name('maintenance.parts.index');
        });

        Route::middleware('permission:tms.maintenance.manage')->group(function () {
            Route::get('/maintenance/parts/create', [\App\Http\Controllers\Admin\Tms\MaintenancePartController::class, 'create'])->name('maintenance.parts.create');
            Route::post('/maintenance/parts', [\App\Http\Controllers\Admin\Tms\MaintenancePartController::class, 'store'])->name('maintenance.parts.store');
            Route::get('/maintenance/parts/{part}/edit', [\App\Http\Controllers\Admin\Tms\MaintenancePartController::class, 'edit'])->name('maintenance.parts.edit')->whereNumber('part');
            Route::put('/maintenance/parts/{part}', [\App\Http\Controllers\Admin\Tms\MaintenancePartController::class, 'update'])->name('maintenance.parts.update')->whereNumber('part');
            Route::delete('/maintenance/parts/{part}', [\App\Http\Controllers\Admin\Tms\MaintenancePartController::class, 'destroy'])->name('maintenance.parts.destroy')->whereNumber('part');
            Route::post('/maintenance/posting/bulk-post', [\App\Http\Controllers\Admin\Tms\MaintenancePostingController::class, 'bulkPost'])->name('maintenance.posting.bulk-post');
            Route::get('/maintenance/vehicles/{vehicle}/bills/create', [\App\Http\Controllers\Admin\Tms\MaintenanceController::class, 'createBill'])->name('maintenance.bills.create')->whereNumber('vehicle');
            Route::post('/maintenance/vehicles/{vehicle}/bills', [\App\Http\Controllers\Admin\Tms\MaintenanceController::class, 'storeBill'])->name('maintenance.bills.store')->whereNumber('vehicle');
            Route::get('/maintenance/bills/{bill}/edit', [\App\Http\Controllers\Admin\Tms\MaintenanceController::class, 'editBill'])->name('maintenance.bills.edit')->whereNumber('bill');
            Route::put('/maintenance/bills/{bill}', [\App\Http\Controllers\Admin\Tms\MaintenanceController::class, 'updateBill'])->name('maintenance.bills.update')->whereNumber('bill');
            Route::delete('/maintenance/bills/{bill}', [\App\Http\Controllers\Admin\Tms\MaintenanceController::class, 'destroyBill'])->name('maintenance.bills.destroy')->whereNumber('bill');
            Route::post('/maintenance/bills/{bill}/post', [\App\Http\Controllers\Admin\Tms\MaintenanceController::class, 'markPostedToFinance'])->name('maintenance.bills.post')->whereNumber('bill');
            Route::post('/maintenance/bills/{bill}/unpost', [\App\Http\Controllers\Admin\Tms\MaintenanceController::class, 'unpostFromFinance'])->name('maintenance.bills.unpost')->whereNumber('bill');
        });

        Route::middleware('permission:tms.reports.view')->group(function () {
            Route::get('/reports', [\App\Http\Controllers\Admin\Tms\ReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/export', [\App\Http\Controllers\Admin\Tms\ReportController::class, 'export'])->name('reports.export');
        });
    });
});

Route::prefix('careers')->name('careers.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Careers\CareersController::class, 'index'])->name('index');
    Route::get('/track', [\App\Http\Controllers\Careers\CareersController::class, 'trackForm'])->name('track');
    Route::post('/track', [\App\Http\Controllers\Careers\CareersController::class, 'track'])->name('track.submit')->middleware('throttle:10,1');
    Route::post('/offer-response', [\App\Http\Controllers\Careers\CareersController::class, 'respondToOffer'])->name('offer.respond')->middleware('throttle:10,1');
    Route::get('/success/{application}', [\App\Http\Controllers\Careers\CareersController::class, 'success'])->name('success')->whereNumber('application');
    Route::get('/{posting}', [\App\Http\Controllers\Careers\CareersController::class, 'show'])->name('show')->whereNumber('posting');
    Route::get('/{posting}/apply', [\App\Http\Controllers\Careers\CareersController::class, 'apply'])->name('apply')->whereNumber('posting');
    Route::post('/{posting}/otp', [\App\Http\Controllers\Careers\CareersController::class, 'sendOtp'])->name('otp.send')->whereNumber('posting')->middleware('throttle:5,1');
    Route::post('/{posting}/apply', [\App\Http\Controllers\Careers\CareersController::class, 'storeApply'])->name('apply.store')->whereNumber('posting')->middleware('throttle:3,60');
});

Route::prefix('employee')->name('employee.')->group(function () {
    Route::middleware('guest:employee')->group(function () {
        Route::get('/login', [EmployeeLoginController::class, 'create'])->name('login');
        Route::post('/login', [EmployeeLoginController::class, 'store'])->name('login.store');
    });

    Route::post('/logout', [EmployeeLoginController::class, 'destroy'])->name('logout')->middleware('auth:employee');

    Route::middleware(['auth:employee', 'employee.portal'])->group(function () {
        Route::redirect('/', '/employee/dashboard');
        Route::get('/dashboard', EmployeeDashboardController::class)->name('dashboard');
        Route::get('/profile', [EmployeeProfileController::class, 'show'])->name('profile');
        Route::get('/attendance', [EmployeeAttendanceController::class, 'index'])->name('attendance');
        Route::get('/attendance/check-in', [EmployeeCheckInController::class, 'create'])->name('attendance.check-in');
        Route::post('/attendance/check-in', [EmployeeCheckInController::class, 'store'])->name('attendance.check-in.store');
        Route::get('/late-acceptance', [EmployeeLateAcceptanceController::class, 'index'])->name('late-acceptance.index');
        Route::get('/late-acceptance/apply', [EmployeeLateAcceptanceController::class, 'create'])->name('late-acceptance.apply');
        Route::post('/late-acceptance/apply', [EmployeeLateAcceptanceController::class, 'store'])->name('late-acceptance.apply.store');
        Route::get('/leave', [EmployeeLeaveController::class, 'index'])->name('leave');
        Route::get('/leave/apply', [EmployeeLeaveController::class, 'create'])->name('leave.apply');
        Route::post('/leave/apply', [EmployeeLeaveController::class, 'store'])->name('leave.apply.store');
        Route::post('/leave/applications/{application}/approve', [EmployeeLeaveController::class, 'approve'])->name('leave.applications.approve');
        Route::post('/leave/applications/{application}/reject', [EmployeeLeaveController::class, 'reject'])->name('leave.applications.reject');
        Route::post('/leave/applications/{application}/cancel', [EmployeeLeaveController::class, 'cancel'])->name('leave.cancel');
        Route::get('/payslips', [EmployeePayslipController::class, 'index'])->name('payslips');
        Route::get('/payslips/{payslip}', [EmployeePayslipController::class, 'show'])->name('payslips.show');
        Route::get('/payslips/{payslip}/print', [EmployeePayslipController::class, 'print'])->name('payslips.print');
        Route::get('/loans', [EmployeeLoanController::class, 'index'])->name('loans');
        Route::get('/loans/{loan}/statement', [EmployeeLoanController::class, 'statement'])->name('loans.statement')->whereNumber('loan');
        Route::get('/loans/apply', [EmployeeLoanController::class, 'create'])->name('loans.apply');
        Route::post('/loans/apply', [EmployeeLoanController::class, 'store'])->name('loans.apply.store');
        Route::get('/roster', [EmployeeRosterController::class, 'index'])->name('roster');
        Route::get('/pf', [EmployeePfController::class, 'index'])->name('pf');
        Route::get('/performance', [EmployeePerformanceController::class, 'index'])->name('performance');
        Route::get('/performance/{review}', [EmployeePerformanceController::class, 'show'])->name('performance.show')->whereNumber('review');
        Route::get('/separation', [\App\Http\Controllers\Employee\SeparationController::class, 'index'])->name('separation');
        Route::post('/separation', [\App\Http\Controllers\Employee\SeparationController::class, 'store'])->name('separation.store');
        Route::delete('/separation', [\App\Http\Controllers\Employee\SeparationController::class, 'cancel'])->name('separation.cancel');
        Route::post('/separation/{separation}/approve', [\App\Http\Controllers\Employee\SeparationController::class, 'approve'])->name('separation.approve')->whereNumber('separation');
        Route::post('/separation/{separation}/reject', [\App\Http\Controllers\Employee\SeparationController::class, 'reject'])->name('separation.reject')->whereNumber('separation');
        Route::get('/notifications', [EmployeeNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count', [EmployeeNotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::patch('/notifications/read-all', [EmployeeNotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::patch('/notifications/{id}/read', [EmployeeNotificationController::class, 'markRead'])->name('notifications.read');

        Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
        Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
        Route::get('/push/vapid-public-key', [PushSubscriptionController::class, 'vapidPublicKey'])->name('push.vapid-public-key');

        Route::prefix('transport')->name('transport.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Employee\Transport\RequestController::class, 'index'])->name('index');
            Route::get('/requests/create', [\App\Http\Controllers\Employee\Transport\RequestController::class, 'create'])->name('requests.create');
            Route::post('/requests', [\App\Http\Controllers\Employee\Transport\RequestController::class, 'store'])->name('requests.store');
            Route::get('/requests/{transportRequest}', [\App\Http\Controllers\Employee\Transport\RequestController::class, 'show'])->name('requests.show')->whereNumber('transportRequest');
            Route::get('/requests/{transportRequest}/edit', [\App\Http\Controllers\Employee\Transport\RequestController::class, 'edit'])->name('requests.edit')->whereNumber('transportRequest');
            Route::put('/requests/{transportRequest}', [\App\Http\Controllers\Employee\Transport\RequestController::class, 'update'])->name('requests.update')->whereNumber('transportRequest');
            Route::post('/requests/{transportRequest}/cancel', [\App\Http\Controllers\Employee\Transport\RequestController::class, 'cancel'])->name('requests.cancel')->whereNumber('transportRequest');
            Route::get('/trips', [\App\Http\Controllers\Employee\Transport\TripController::class, 'index'])->name('trips');
            Route::post('/trips/{trip}/start', [\App\Http\Controllers\Employee\Transport\TripController::class, 'start'])->name('trips.start')->whereNumber('trip');
            Route::post('/trips/{trip}/end', [\App\Http\Controllers\Employee\Transport\TripController::class, 'end'])->name('trips.end')->whereNumber('trip');
            Route::get('/odometer', [\App\Http\Controllers\Employee\Transport\OdometerController::class, 'index'])->name('odometer');
            Route::post('/odometer/morning', [\App\Http\Controllers\Employee\Transport\OdometerController::class, 'storeMorning'])->name('odometer.morning');
            Route::post('/odometer/{odometer}/evening', [\App\Http\Controllers\Employee\Transport\OdometerController::class, 'storeEvening'])->name('odometer.evening')->whereNumber('odometer');
        });
    });
});

Route::prefix('rental')->name('rental.')->group(function () {
    Route::middleware('guest:rental_driver')->group(function () {
        Route::get('/login', [\App\Http\Controllers\Rental\Auth\LoginController::class, 'create'])->name('login');
        Route::post('/login', [\App\Http\Controllers\Rental\Auth\LoginController::class, 'store'])->name('login.store');
    });

    Route::post('/logout', [\App\Http\Controllers\Rental\Auth\LoginController::class, 'destroy'])->name('logout')->middleware('auth:rental_driver');

    Route::middleware(['auth:rental_driver', 'rental.portal'])->group(function () {
        Route::redirect('/', '/rental/dashboard');
        Route::get('/dashboard', \App\Http\Controllers\Rental\DashboardController::class)->name('dashboard');
        Route::get('/trips', [\App\Http\Controllers\Rental\TripController::class, 'index'])->name('trips');
        Route::post('/trips/{trip}/start', [\App\Http\Controllers\Rental\TripController::class, 'start'])->name('trips.start')->whereNumber('trip');
        Route::post('/trips/{trip}/end', [\App\Http\Controllers\Rental\TripController::class, 'end'])->name('trips.end')->whereNumber('trip');
        Route::get('/odometer', [\App\Http\Controllers\Rental\OdometerController::class, 'index'])->name('odometer');
        Route::get('/odometer/morning/create', [\App\Http\Controllers\Rental\OdometerController::class, 'createMorning'])->name('odometer.morning.create');
        Route::post('/odometer/morning', [\App\Http\Controllers\Rental\OdometerController::class, 'storeMorning'])->name('odometer.morning.store');
        Route::get('/odometer/{odometer}/evening', [\App\Http\Controllers\Rental\OdometerController::class, 'createEvening'])->name('odometer.evening.create')->whereNumber('odometer');
        Route::post('/odometer/{odometer}/evening', [\App\Http\Controllers\Rental\OdometerController::class, 'storeEvening'])->name('odometer.evening.store')->whereNumber('odometer');
        Route::get('/notifications', [\App\Http\Controllers\Rental\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count', [\App\Http\Controllers\Rental\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::patch('/notifications/read-all', [\App\Http\Controllers\Rental\NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::patch('/notifications/{id}/read', [\App\Http\Controllers\Rental\NotificationController::class, 'markRead'])->name('notifications.read');

        Route::post('/push/subscribe', [\App\Http\Controllers\Rental\PushSubscriptionController::class, 'store'])->name('push.subscribe');
        Route::delete('/push/unsubscribe', [\App\Http\Controllers\Rental\PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
        Route::get('/push/vapid-public-key', [\App\Http\Controllers\Rental\PushSubscriptionController::class, 'vapidPublicKey'])->name('push.vapid-public-key');
    });
});
