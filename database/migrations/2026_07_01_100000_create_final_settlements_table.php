<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_final_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->string('separation_type', 20);
            $table->date('last_working_day');
            $table->string('status', 20)->default('draft');
            $table->decimal('unpaid_salary', 14, 2)->default(0);
            $table->decimal('leave_encashment', 14, 2)->default(0);
            $table->decimal('gratuity_amount', 14, 2)->default(0);
            $table->decimal('pf_withdrawal', 14, 2)->default(0);
            $table->decimal('loan_deduction', 14, 2)->default(0);
            $table->decimal('tax_deduction', 14, 2)->default(0);
            $table->decimal('other_earnings', 14, 2)->default(0);
            $table->decimal('other_deductions', 14, 2)->default(0);
            $table->decimal('net_payable', 14, 2)->default(0);
            $table->json('breakdown')->nullable();
            $table->json('clearance')->nullable();
            $table->foreignId('gratuity_settlement_id')->nullable()->constrained('hrm_gratuity_settlements')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique('employee_id', 'hrm_final_settlement_employee_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_final_settlements');
    }
};
