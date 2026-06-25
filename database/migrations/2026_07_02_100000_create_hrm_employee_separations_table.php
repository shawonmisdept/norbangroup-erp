<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->date('separation_date')->nullable()->after('contract_end_date');
            $table->date('last_working_day')->nullable()->after('separation_date');
        });

        Schema::create('hrm_employee_separations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->string('separation_type', 20);
            $table->string('source', 20)->default('admin');
            $table->string('status', 20)->default('pending');
            $table->date('application_date');
            $table->date('last_working_day');
            $table->unsignedSmallInteger('notice_period_days')->nullable();
            $table->text('reason')->nullable();
            $table->text('remarks')->nullable();
            $table->string('attachment_path')->nullable();
            $table->unsignedTinyInteger('current_approval_step')->default(1);
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['factory_id', 'status'], 'hrm_sep_factory_status_idx');
            $table->index(['employee_id', 'status'], 'hrm_sep_employee_status_idx');
        });

        Schema::create('hrm_employee_separation_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_separation_id')->constrained('hrm_employee_separations')->cascadeOnDelete();
            $table->unsignedTinyInteger('step');
            $table->string('step_label', 40);
            $table->string('status', 20)->default('pending');
            $table->foreignId('approver_employee_id')->nullable()->constrained('hrm_employees')->nullOnDelete();
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('acted_by_employee_id')->nullable()->constrained('hrm_employees')->nullOnDelete();
            $table->timestamp('acted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_separation_id', 'step'], 'hrm_sep_appr_sep_step_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_employee_separation_approvals');
        Schema::dropIfExists('hrm_employee_separations');

        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->dropColumn(['separation_date', 'last_working_day']);
        });
    }
};
