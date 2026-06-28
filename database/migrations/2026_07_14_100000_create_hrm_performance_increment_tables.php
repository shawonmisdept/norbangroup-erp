<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_performance_increment_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('min_score', 5, 2)->default(0);
            $table->decimal('max_score', 5, 2)->default(100);
            $table->decimal('increment_percent', 5, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['factory_id', 'is_active'], 'hrm_perf_incr_band_factory_idx');
        });

        Schema::create('hrm_performance_increment_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_cycle_id')->nullable()->constrained('hrm_performance_cycles')->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('name');
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['factory_id', 'year', 'status'], 'hrm_perf_incr_run_factory_idx');
        });

        Schema::create('hrm_performance_increment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_increment_run_id');
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->foreignId('performance_review_id')->nullable()->constrained('hrm_performance_reviews')->nullOnDelete();
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->string('band_name')->nullable();
            $table->decimal('increment_percent', 5, 2)->default(0);
            $table->decimal('override_increment_percent', 5, 2)->nullable();
            $table->decimal('previous_gross', 12, 2)->default(0);
            $table->decimal('suggested_new_gross', 12, 2)->default(0);
            $table->decimal('override_new_gross', 12, 2)->nullable();
            $table->decimal('final_new_gross', 12, 2)->default(0);
            $table->decimal('increment_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('salary_increment_log_id')->nullable();
            $table->text('error_message')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('performance_increment_run_id', 'hrm_perf_incr_item_run_fk')
                ->references('id')->on('hrm_performance_increment_runs')->cascadeOnDelete();
            $table->foreign('salary_increment_log_id', 'hrm_perf_incr_item_log_fk')
                ->references('id')->on('hrm_salary_increment_logs')->nullOnDelete();
            $table->unique(['performance_increment_run_id', 'employee_id'], 'hrm_perf_incr_item_run_emp_uq');
        });

        Schema::table('hrm_salary_increment_logs', function (Blueprint $table) {
            $table->foreignId('performance_review_id')->nullable()->after('employee_id')
                ->constrained('hrm_performance_reviews')->nullOnDelete();
            $table->unsignedBigInteger('performance_increment_run_id')->nullable()->after('performance_review_id');
            $table->foreign('performance_increment_run_id', 'hrm_salary_incr_log_perf_run_fk')
                ->references('id')->on('hrm_performance_increment_runs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hrm_salary_increment_logs', function (Blueprint $table) {
            $table->dropForeign('hrm_salary_incr_log_perf_run_fk');
            $table->dropConstrainedForeignId('performance_review_id');
        });

        Schema::dropIfExists('hrm_performance_increment_items');
        Schema::dropIfExists('hrm_performance_increment_runs');
        Schema::dropIfExists('hrm_performance_increment_bands');
    }
};
