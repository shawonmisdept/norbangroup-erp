<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->timestamp('probation_passed_at')->nullable()->after('confirmation_date');
        });

        Schema::create('hrm_performance_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->json('cycle_types')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['factory_id', 'is_active'], 'hrm_perf_tpl_factory_active_idx');
        });

        Schema::create('hrm_performance_template_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('hrm_performance_templates')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('label');
            $table->string('criterion_type', 10);
            $table->decimal('weight', 5, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'code'], 'hrm_perf_tpl_crit_code_uq');
        });

        Schema::create('hrm_performance_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->string('cycle_type', 20);
            $table->string('name');
            $table->unsignedSmallInteger('year')->nullable();
            $table->date('period_from');
            $table->date('period_to');
            $table->string('status', 20)->default('open');
            $table->foreignId('template_id')->nullable()->constrained('hrm_performance_templates')->nullOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->timestamps();

            $table->index(['factory_id', 'cycle_type', 'status'], 'hrm_perf_cycle_factory_type_idx');
        });

        Schema::create('hrm_performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cycle_id')->constrained('hrm_performance_cycles')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('hrm_performance_templates')->cascadeOnDelete();
            $table->string('cycle_type', 20);
            $table->string('status', 20)->default('pending_rating');
            $table->date('period_from');
            $table->date('period_to');
            $table->foreignId('reporting_to_id')->nullable()->constrained('hrm_employees')->nullOnDelete();
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->json('auto_metrics')->nullable();
            $table->boolean('manual_fallback')->default(false);
            $table->text('probation_recommendation')->nullable();
            $table->boolean('apply_confirmation')->default(false);
            $table->string('blocked_reason')->nullable();
            $table->foreignId('rated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rated_on_behalf_of_id')->nullable()->constrained('hrm_employees')->nullOnDelete();
            $table->timestamp('rated_at')->nullable();
            $table->foreignId('hr_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_approved_at')->nullable();
            $table->foreignId('hr_rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_rejected_at')->nullable();
            $table->text('hr_rejection_reason')->nullable();
            $table->text('rating_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['factory_id', 'status'], 'hrm_perf_rev_factory_status_idx');
            $table->index(['employee_id', 'cycle_type'], 'hrm_perf_rev_employee_type_idx');
            $table->index(['cycle_id', 'status'], 'hrm_perf_rev_cycle_status_idx');
        });

        Schema::create('hrm_performance_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('hrm_performance_reviews')->cascadeOnDelete();
            $table->foreignId('criterion_id')->nullable()->constrained('hrm_performance_template_criteria')->nullOnDelete();
            $table->string('code', 40);
            $table->string('label');
            $table->string('criterion_type', 10);
            $table->decimal('weight', 5, 2);
            $table->decimal('score', 5, 2)->nullable();
            $table->boolean('is_auto')->default(false);
            $table->json('auto_source')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['review_id', 'code'], 'hrm_perf_score_review_code_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_performance_scores');
        Schema::dropIfExists('hrm_performance_reviews');
        Schema::dropIfExists('hrm_performance_cycles');
        Schema::dropIfExists('hrm_performance_template_criteria');
        Schema::dropIfExists('hrm_performance_templates');

        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->dropColumn('probation_passed_at');
        });
    }
};
