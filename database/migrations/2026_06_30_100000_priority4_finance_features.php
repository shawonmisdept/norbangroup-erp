<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_tax_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->string('label', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['factory_id', 'label'], 'hrm_tax_year_factory_label_uq');
        });

        Schema::create('hrm_tax_slabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_year_id')->constrained('hrm_tax_years')->cascadeOnDelete();
            $table->decimal('min_income', 14, 2)->default(0);
            $table->decimal('max_income', 14, 2)->nullable();
            $table->decimal('rate_percent', 5, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('hrm_employee_tax_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->foreignId('tax_year_id')->nullable()->constrained('hrm_tax_years')->nullOnDelete();
            $table->foreignId('payroll_period_id')->nullable()->constrained('hrm_payroll_periods')->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('taxable_income', 12, 2)->default(0);
            $table->decimal('tds_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'year', 'month'], 'hrm_tax_ledger_emp_month_uq');
        });

        Schema::create('hrm_pf_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->decimal('employee_rate_pct', 5, 2)->default(7);
            $table->decimal('employer_rate_pct', 5, 2)->default(7.5);
            $table->decimal('balance', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->date('opened_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id'], 'hrm_pf_account_employee_uq');
        });

        Schema::create('hrm_pf_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pf_account_id')->constrained('hrm_pf_accounts')->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->nullable()->constrained('hrm_payroll_periods')->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('employee_amount', 12, 2)->default(0);
            $table->decimal('employer_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['pf_account_id', 'year', 'month'], 'hrm_pf_contrib_account_month_uq');
        });

        Schema::create('hrm_loan_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->string('loan_type', 20)->default('advance');
            $table->decimal('principal', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->decimal('emi_amount', 12, 2);
            $table->unsignedSmallInteger('total_installments')->default(1);
            $table->unsignedSmallInteger('paid_installments')->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['factory_id', 'status']);
        });

        Schema::create('hrm_loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_account_id')->constrained('hrm_loan_accounts')->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->nullable()->constrained('hrm_payroll_periods')->nullOnDelete();
            $table->unsignedSmallInteger('installment_no');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->unique(['loan_account_id', 'installment_no'], 'hrm_loan_inst_loan_no_uq');
        });

        Schema::create('hrm_shift_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['factory_id', 'start_date']);
        });

        Schema::create('hrm_shift_roster_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_id')->constrained('hrm_shift_rosters')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->date('roster_date');
            $table->foreignId('shift_id')->constrained('hrm_shifts')->cascadeOnDelete();
            $table->foreignId('line_id')->nullable()->constrained('hrm_lines')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'roster_date'], 'hrm_roster_entry_emp_date_uq');
        });

        Schema::table('hrm_payroll_items', function (Blueprint $table) {
            $table->decimal('tds_amount', 12, 2)->default(0)->after('other_deduction');
            $table->decimal('pf_employee_amount', 12, 2)->default(0)->after('tds_amount');
            $table->decimal('pf_employer_amount', 12, 2)->default(0)->after('pf_employee_amount');
            $table->decimal('loan_deduction', 12, 2)->default(0)->after('pf_employer_amount');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_payroll_items', function (Blueprint $table) {
            $table->dropColumn(['tds_amount', 'pf_employee_amount', 'pf_employer_amount', 'loan_deduction']);
        });

        Schema::dropIfExists('hrm_shift_roster_entries');
        Schema::dropIfExists('hrm_shift_rosters');
        Schema::dropIfExists('hrm_loan_installments');
        Schema::dropIfExists('hrm_loan_accounts');
        Schema::dropIfExists('hrm_pf_contributions');
        Schema::dropIfExists('hrm_pf_accounts');
        Schema::dropIfExists('hrm_employee_tax_ledgers');
        Schema::dropIfExists('hrm_tax_slabs');
        Schema::dropIfExists('hrm_tax_years');
    }
};
