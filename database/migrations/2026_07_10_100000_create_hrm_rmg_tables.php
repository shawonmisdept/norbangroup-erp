<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createIfNotExists('hrm_worker_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->foreignId('to_factory_id')->nullable()->constrained('factories')->nullOnDelete();
            $table->foreignId('to_line_id')->nullable()->constrained('hrm_lines')->nullOnDelete();
            $table->foreignId('to_floor_id')->nullable()->constrained('hrm_floors')->nullOnDelete();
            $table->foreignId('to_building_id')->nullable()->constrained('hrm_buildings')->nullOnDelete();
            $table->date('effective_date');
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->createIfNotExists('hrm_osd_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->string('movement_type', 30);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('destination')->nullable();
            $table->text('purpose')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->createIfNotExists('hrm_gate_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->date('pass_date');
            $table->time('out_time')->nullable();
            $table->time('expected_in_time')->nullable();
            $table->string('destination')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->createIfNotExists('hrm_manpower_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('line_id')->constrained('hrm_lines')->cascadeOnDelete();
            $table->date('plan_date');
            $table->unsignedInteger('required_count');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['factory_id', 'line_id', 'plan_date']);
        });

        $this->createIfNotExists('hrm_proxy_punch_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('attendance_raw_punch_id')->constrained('hrm_attendance_raw_punches')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('hrm_employees')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('open');
            $table->foreignId('flagged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        $this->createIfNotExists('hrm_canteen_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->unsignedInteger('meal_count')->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'period_year', 'period_month'], 'hrm_canteen_emp_period_unique');
        });

        $this->createIfNotExists('hrm_medical_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->date('visit_date');
            $table->string('complaint')->nullable();
            $table->string('diagnosis')->nullable();
            $table->string('treatment')->nullable();
            $table->boolean('referred')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->createIfNotExists('hrm_training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->string('training_type', 40);
            $table->string('title');
            $table->string('provider')->nullable();
            $table->date('training_date');
            $table->date('expiry_date')->nullable();
            $table->string('certificate_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->createIfNotExists('hrm_sub_contract_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('line_id')->nullable()->constrained('hrm_lines')->nullOnDelete();
            $table->string('agency_name');
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('nid_number', 30)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfNotExists('hrm_production_incentives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('line_id')->constrained('hrm_lines')->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->unsignedInteger('output_qty')->default(0);
            $table->decimal('incentive_rate', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->createIfNotExists('hrm_salary_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->nullable()->constrained('hrm_payroll_periods')->nullOnDelete();
            $table->text('reason');
            $table->date('hold_from');
            $table->date('hold_until')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->createIfNotExists('hrm_buyer_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('buyers')->cascadeOnDelete();
            $table->string('name');
            $table->date('date');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_buyer_holidays');
        Schema::dropIfExists('hrm_salary_holds');
        Schema::dropIfExists('hrm_production_incentives');
        Schema::dropIfExists('hrm_sub_contract_workers');
        Schema::dropIfExists('hrm_training_records');
        Schema::dropIfExists('hrm_medical_visits');
        Schema::dropIfExists('hrm_canteen_deductions');
        Schema::dropIfExists('hrm_proxy_punch_flags');
        Schema::dropIfExists('hrm_manpower_plans');
        Schema::dropIfExists('hrm_gate_passes');
        Schema::dropIfExists('hrm_osd_movements');
        Schema::dropIfExists('hrm_worker_transfers');
    }

    private function createIfNotExists(string $table, callable $callback): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $callback);
        }
    }
};
