<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_attendance_policies', function (Blueprint $table) {
            $table->unsignedTinyInteger('consecutive_late_grace_days')->default(3)->after('late_grace_minutes');
            $table->string('late_deduction_basis', 20)->default('basic')->after('consecutive_late_grace_days');
            $table->boolean('late_streak_resets_on_absent')->default(true)->after('late_deduction_basis');
        });

        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->boolean('late_acceptance_enabled')->default(false)->after('status');
        });

        Schema::create('hrm_late_acceptance_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date'], 'hrm_late_accept_emp_date_uq');
            $table->index(['factory_id', 'status']);
        });

        Schema::table('hrm_attendance_daily_logs', function (Blueprint $table) {
            $table->boolean('is_late_forgiven')->default(false)->after('status');
            $table->unsignedTinyInteger('late_streak_day')->nullable()->after('is_late_forgiven');
            $table->decimal('late_deduction_amount', 12, 2)->default(0)->after('late_streak_day');
            $table->foreignId('late_acceptance_application_id')
                ->nullable()
                ->after('late_deduction_amount')
                ->constrained('hrm_late_acceptance_applications')
                ->nullOnDelete();
        });

        Schema::table('hrm_payroll_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('late_forgiven_days')->default(0)->after('late_days');
            $table->unsignedSmallInteger('late_charge_days')->default(0)->after('late_forgiven_days');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_payroll_items', function (Blueprint $table) {
            $table->dropColumn(['late_forgiven_days', 'late_charge_days']);
        });

        Schema::table('hrm_attendance_daily_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('late_acceptance_application_id');
            $table->dropColumn(['is_late_forgiven', 'late_streak_day', 'late_deduction_amount']);
        });

        Schema::dropIfExists('hrm_late_acceptance_applications');

        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->dropColumn('late_acceptance_enabled');
        });

        Schema::table('hrm_attendance_policies', function (Blueprint $table) {
            $table->dropColumn(['consecutive_late_grace_days', 'late_deduction_basis', 'late_streak_resets_on_absent']);
        });
    }
};
