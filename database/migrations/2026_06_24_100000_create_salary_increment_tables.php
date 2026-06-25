<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_salary_increment_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_grade_id')->nullable()->constrained('hrm_salary_grades')->nullOnDelete();
            $table->string('name', 80);
            $table->string('increment_type', 20)->default('percentage');
            $table->decimal('increment_value', 12, 2);
            $table->unsignedSmallInteger('min_tenure_months')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['factory_id', 'is_active'], 'hrm_salary_incr_rule_factory_idx');
        });

        Schema::create('hrm_salary_increment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_increment_rule_id')->nullable()->constrained('hrm_salary_increment_rules')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->decimal('previous_gross', 12, 2);
            $table->decimal('new_gross', 12, 2);
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->index(['employee_id', 'applied_at'], 'hrm_salary_incr_log_emp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_salary_increment_logs');
        Schema::dropIfExists('hrm_salary_increment_rules');
    }
};
