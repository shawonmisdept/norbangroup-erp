<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_performance_bonus_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('min_score', 5, 2)->default(0);
            $table->decimal('max_score', 5, 2)->default(100);
            $table->decimal('bonus_percent', 5, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['factory_id', 'is_active'], 'hrm_perf_bonus_band_factory_idx');
        });

        Schema::create('hrm_performance_bonus_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_cycle_id')->nullable()->constrained('hrm_performance_cycles')->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('name');
            $table->string('bonus_base', 10)->default('gross');
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['factory_id', 'year', 'status'], 'hrm_perf_bonus_run_factory_idx');
        });

        Schema::create('hrm_performance_bonus_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_bonus_run_id')->constrained('hrm_performance_bonus_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->foreignId('performance_review_id')->nullable()->constrained('hrm_performance_reviews')->nullOnDelete();
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->string('band_name')->nullable();
            $table->decimal('bonus_percent', 5, 2)->default(0);
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('bonus_amount', 12, 2)->default(0);
            $table->decimal('override_amount', 12, 2)->nullable();
            $table->decimal('final_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['performance_bonus_run_id', 'employee_id'], 'hrm_perf_bonus_item_run_emp_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_performance_bonus_items');
        Schema::dropIfExists('hrm_performance_bonus_runs');
        Schema::dropIfExists('hrm_performance_bonus_bands');
    }
};
