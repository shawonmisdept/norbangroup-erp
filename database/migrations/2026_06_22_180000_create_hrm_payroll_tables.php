<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->string('pay_type', 20)->default('wages');
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('daily_wage', 10, 2)->default(0);
            $table->decimal('hra', 10, 2)->default(0);
            $table->decimal('medical', 10, 2)->default(0);
            $table->decimal('conveyance', 10, 2)->default(0);
            $table->decimal('other_allowance', 10, 2)->default(0);
            $table->string('payment_method', 20)->default('bank');
            $table->string('bank_account', 40)->nullable();
            $table->date('effective_from')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('employee_id', 'hrm_salary_struct_employee_uq');
            $table->index(['factory_id', 'is_active'], 'hrm_salary_struct_factory_active_idx');
        });

        Schema::create('hrm_payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('draft');
            $table->foreignId('attendance_period_id')->nullable()->constrained('hrm_attendance_periods')->nullOnDelete();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('frozen_at')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('frozen_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['factory_id', 'year', 'month'], 'hrm_payroll_period_factory_ym_uq');
            $table->index(['factory_id', 'status'], 'hrm_payroll_period_factory_status_idx');
        });

        Schema::create('hrm_payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('hrm_payroll_periods')->cascadeOnDelete();
            $table->string('status', 20)->default('running');
            $table->unsignedInteger('employee_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('run_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('hrm_payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained('hrm_payroll_periods')->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->nullable()->constrained('hrm_payroll_runs')->nullOnDelete();
            $table->string('pay_type', 20);
            $table->unsignedSmallInteger('present_days')->default(0);
            $table->unsignedSmallInteger('absent_days')->default(0);
            $table->unsignedSmallInteger('leave_days')->default(0);
            $table->unsignedSmallInteger('late_days')->default(0);
            $table->unsignedSmallInteger('half_days')->default(0);
            $table->decimal('basic_amount', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('gross_pay', 12, 2)->default(0);
            $table->decimal('absent_deduction', 12, 2)->default(0);
            $table->decimal('late_deduction', 12, 2)->default(0);
            $table->decimal('other_deduction', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->string('payment_method', 20)->default('bank');
            $table->string('bank_account', 40)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'payroll_period_id'], 'hrm_payroll_item_emp_period_uq');
            $table->index(['payroll_period_id', 'factory_id'], 'hrm_payroll_item_period_factory_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_payroll_items');
        Schema::dropIfExists('hrm_payroll_runs');
        Schema::dropIfExists('hrm_payroll_periods');
        Schema::dropIfExists('hrm_salary_structures');
    }
};
