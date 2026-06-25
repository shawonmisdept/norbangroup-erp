<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_employee_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->string('movement_type', 20);
            $table->string('status', 20)->default('pending');

            $table->foreignId('from_designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->foreignId('to_designation_id')->constrained('designations')->cascadeOnDelete();
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('from_worker_category_id')->nullable()->constrained('hrm_worker_categories')->nullOnDelete();
            $table->foreignId('to_worker_category_id')->nullable()->constrained('hrm_worker_categories')->nullOnDelete();
            $table->foreignId('from_reporting_to_id')->nullable()->constrained('hrm_employees')->nullOnDelete();
            $table->foreignId('to_reporting_to_id')->nullable()->constrained('hrm_employees')->nullOnDelete();

            $table->foreignId('from_salary_grade_id')->nullable()->constrained('hrm_salary_grades')->nullOnDelete();
            $table->foreignId('to_salary_grade_id')->nullable()->constrained('hrm_salary_grades')->nullOnDelete();
            $table->decimal('from_gross_salary', 12, 2)->nullable();
            $table->decimal('to_gross_salary', 12, 2)->nullable();
            $table->boolean('apply_salary_change')->default(false);

            $table->date('effective_date');
            $table->text('reason')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['factory_id', 'status'], 'hrm_promo_factory_status_idx');
            $table->index(['employee_id', 'status'], 'hrm_promo_employee_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_employee_promotions');
    }
};
