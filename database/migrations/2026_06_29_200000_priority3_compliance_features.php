<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_worker_categories', function (Blueprint $table) {
            $table->decimal('minimum_wage', 10, 2)->nullable()->after('description');
        });

        Schema::table('hrm_attendance_policies', function (Blueprint $table) {
            $table->decimal('ot_multiplier_normal', 4, 2)->default(2.00)->after('max_monthly_ot_hours');
            $table->decimal('ot_multiplier_holiday', 4, 2)->default(2.00)->after('ot_multiplier_normal');
            $table->decimal('ot_multiplier_night', 4, 2)->default(2.00)->after('ot_multiplier_holiday');
            $table->decimal('max_daily_hours', 4, 1)->default(10.0)->after('ot_multiplier_night');
            $table->decimal('max_weekly_hours', 4, 1)->default(60.0)->after('max_daily_hours');
            $table->unsignedTinyInteger('min_employment_age')->default(18)->after('max_weekly_hours');
        });

        Schema::create('hrm_maternity_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->foreignId('leave_application_id')->nullable()->constrained('hrm_leave_applications')->nullOnDelete();
            $table->date('expected_delivery_date')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedTinyInteger('paid_weeks')->default(8);
            $table->unsignedTinyInteger('unpaid_weeks')->default(8);
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['factory_id', 'status']);
            $table->index(['employee_id', 'start_date']);
        });

        Schema::create('hrm_bonus_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->string('bonus_type', 30)->default('eid_ul_fitr');
            $table->unsignedSmallInteger('year');
            $table->date('bonus_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['factory_id', 'bonus_type', 'year'], 'hrm_bonus_run_factory_type_year_uq');
        });

        Schema::create('hrm_bonus_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bonus_run_id')->constrained('hrm_bonus_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->decimal('basic_avg', 12, 2)->default(0);
            $table->unsignedTinyInteger('months_worked')->default(0);
            $table->decimal('bonus_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['bonus_run_id', 'employee_id'], 'hrm_bonus_item_run_employee_uq');
        });

        Schema::create('hrm_gratuity_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->date('separation_date');
            $table->decimal('years_of_service', 5, 2)->default(0);
            $table->decimal('last_basic_salary', 12, 2)->default(0);
            $table->decimal('gratuity_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('calculated');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['factory_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_gratuity_settlements');
        Schema::dropIfExists('hrm_bonus_items');
        Schema::dropIfExists('hrm_bonus_runs');
        Schema::dropIfExists('hrm_maternity_transactions');

        Schema::table('hrm_attendance_policies', function (Blueprint $table) {
            $table->dropColumn([
                'ot_multiplier_normal',
                'ot_multiplier_holiday',
                'ot_multiplier_night',
                'max_daily_hours',
                'max_weekly_hours',
                'min_employment_age',
            ]);
        });

        Schema::table('hrm_worker_categories', function (Blueprint $table) {
            $table->dropColumn('minimum_wage');
        });
    }
};
