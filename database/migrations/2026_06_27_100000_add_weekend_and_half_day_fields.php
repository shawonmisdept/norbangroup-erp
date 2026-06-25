<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->json('weekend_days')->nullable()->after('late_acceptance_enabled');
            $table->boolean('weekend_ot_allowed')->default(false)->after('weekend_days');
            $table->decimal('half_day_pay_ratio', 5, 2)->nullable()->after('weekend_ot_allowed');
        });

        Schema::table('hrm_attendance_policies', function (Blueprint $table) {
            $table->unsignedSmallInteger('full_day_minutes')->default(480)->after('min_half_day_minutes');
            $table->decimal('default_half_day_pay_ratio', 5, 2)->default(0.5)->after('full_day_minutes');
        });

        Schema::table('hrm_attendance_daily_logs', function (Blueprint $table) {
            $table->string('half_day_type', 20)->nullable()->after('is_late_forgiven');
            $table->decimal('half_day_pay_ratio', 5, 2)->nullable()->after('half_day_type');
            $table->boolean('is_manual_half_day')->default(false)->after('half_day_pay_ratio');
            $table->text('half_day_notes')->nullable()->after('is_manual_half_day');
        });

        Schema::table('hrm_payroll_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('half_day_first')->default(0)->after('half_days');
            $table->unsignedSmallInteger('half_day_second')->default(0)->after('half_day_first');
            $table->decimal('half_day_paid_units', 8, 2)->default(0)->after('half_day_second');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_payroll_items', function (Blueprint $table) {
            $table->dropColumn(['half_day_first', 'half_day_second', 'half_day_paid_units']);
        });

        Schema::table('hrm_attendance_daily_logs', function (Blueprint $table) {
            $table->dropColumn(['half_day_type', 'half_day_pay_ratio', 'is_manual_half_day', 'half_day_notes']);
        });

        Schema::table('hrm_attendance_policies', function (Blueprint $table) {
            $table->dropColumn(['full_day_minutes', 'default_half_day_pay_ratio']);
        });

        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->dropColumn(['weekend_days', 'weekend_ot_allowed', 'half_day_pay_ratio']);
        });
    }
};
