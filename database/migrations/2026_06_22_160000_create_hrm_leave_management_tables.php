<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_leave_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('hrm_leave_types')->cascadeOnDelete();
            $table->decimal('days_per_year', 5, 1)->default(0);
            $table->unsignedTinyInteger('min_days_notice')->default(0);
            $table->unsignedTinyInteger('requires_medical_after_days')->nullable();
            $table->boolean('requires_attachment')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['factory_id', 'leave_type_id'], 'hrm_leave_policy_factory_type_uq');
        });

        Schema::create('hrm_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('hrm_leave_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('entitled_days', 5, 1)->default(0);
            $table->decimal('used_days', 5, 1)->default(0);
            $table->decimal('pending_days', 5, 1)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year'], 'hrm_leave_bal_emp_type_yr_uq');
            $table->index(['factory_id', 'year'], 'hrm_leave_bal_factory_year_idx');
        });

        Schema::create('hrm_leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('hrm_leave_types')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 1);
            $table->text('reason')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->unsignedTinyInteger('current_approval_step')->default(1);
            $table->timestamps();

            $table->index(['factory_id', 'status'], 'hrm_leave_app_factory_status_idx');
            $table->index(['employee_id', 'status'], 'hrm_leave_app_employee_status_idx');
            $table->index(['start_date', 'end_date'], 'hrm_leave_app_dates_idx');
        });

        Schema::create('hrm_leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_application_id')->constrained('hrm_leave_applications')->cascadeOnDelete();
            $table->unsignedTinyInteger('step');
            $table->string('step_label', 40);
            $table->string('status', 20)->default('pending');
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['leave_application_id', 'step'], 'hrm_leave_appr_app_step_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_leave_approvals');
        Schema::dropIfExists('hrm_leave_applications');
        Schema::dropIfExists('hrm_leave_balances');
        Schema::dropIfExists('hrm_leave_policies');
    }
};
