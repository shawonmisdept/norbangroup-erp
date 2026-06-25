<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->date('probation_end_date')->nullable()->after('confirmation_date');
            $table->date('contract_end_date')->nullable()->after('probation_end_date');
        });

        Schema::table('hrm_attendance_policies', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_monthly_ot_hours')->default(104)->after('full_day_minutes');
        });

        Schema::create('hrm_employee_service_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->foreignId('factory_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 40);
            $table->string('field_name', 60)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('description');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('effective_date')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'created_at']);
        });

        Schema::table('app_settings', function (Blueprint $table) {
            $table->boolean('notify_popup_hrm_leave')->default(true)->after('notify_popup_hrm_manual_punch');
            $table->boolean('notify_mail_hrm_leave')->default(true)->after('notify_popup_hrm_leave');
            $table->boolean('notify_popup_hrm_sync_failed')->default(true)->after('notify_mail_hrm_leave');
            $table->boolean('notify_popup_hrm_daily_attendance')->default(true)->after('notify_popup_hrm_sync_failed');
            $table->boolean('notify_popup_hrm_contract_expiry')->default(true)->after('notify_popup_hrm_daily_attendance');
            $table->boolean('notify_popup_hrm_probation_end')->default(true)->after('notify_popup_hrm_contract_expiry');
            $table->boolean('notify_popup_hrm_ot_limit')->default(true)->after('notify_popup_hrm_probation_end');
            $table->boolean('notify_mail_hrm_payslip')->default(true)->after('notify_popup_hrm_ot_limit');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'notify_popup_hrm_leave',
                'notify_mail_hrm_leave',
                'notify_popup_hrm_sync_failed',
                'notify_popup_hrm_daily_attendance',
                'notify_popup_hrm_contract_expiry',
                'notify_popup_hrm_probation_end',
                'notify_popup_hrm_ot_limit',
                'notify_mail_hrm_payslip',
            ]);
        });

        Schema::dropIfExists('hrm_employee_service_histories');

        Schema::table('hrm_attendance_policies', function (Blueprint $table) {
            $table->dropColumn('max_monthly_ot_hours');
        });

        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->dropColumn(['probation_end_date', 'contract_end_date']);
        });
    }
};
