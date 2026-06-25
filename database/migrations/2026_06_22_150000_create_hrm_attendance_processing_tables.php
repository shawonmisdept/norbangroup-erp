<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('late_grace_minutes')->default(10);
            $table->unsignedSmallInteger('early_leave_grace_minutes')->default(10);
            $table->unsignedSmallInteger('min_half_day_minutes')->default(240);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hrm_attendance_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('draft');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('frozen_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['factory_id', 'year', 'month']);
            $table->index(['factory_id', 'status']);
        });

        Schema::create('hrm_attendance_daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->foreignId('attendance_period_id')->nullable()->constrained('hrm_attendance_periods')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('hrm_shifts')->nullOnDelete();
            $table->date('attendance_date');
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->time('expected_in')->nullable();
            $table->time('expected_out')->nullable();
            $table->unsignedSmallInteger('work_minutes')->default(0);
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('early_leave_minutes')->default(0);
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->unsignedSmallInteger('punch_count')->default(0);
            $table->string('status', 20)->default('present');
            $table->boolean('is_manual')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date'], 'hrm_att_log_employee_date_uq');
            $table->index(['factory_id', 'attendance_date'], 'hrm_att_log_factory_date_idx');
            $table->index(['attendance_period_id', 'attendance_date'], 'hrm_att_log_period_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_attendance_daily_logs');
        Schema::dropIfExists('hrm_attendance_periods');
        Schema::dropIfExists('hrm_attendance_policies');
    }
};
