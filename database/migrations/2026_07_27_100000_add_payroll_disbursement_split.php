<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_salary_structures', function (Blueprint $table) {
            $table->decimal('bank_disbursement_amount', 12, 2)->nullable()->after('bank_account');
        });

        Schema::table('hrm_payroll_items', function (Blueprint $table) {
            $table->decimal('bank_pay_amount', 12, 2)->default(0)->after('net_pay');
            $table->decimal('cash_pay_amount', 12, 2)->default(0)->after('bank_pay_amount');
            $table->boolean('disbursement_override')->default(false)->after('cash_pay_amount');
            $table->timestamp('cash_disbursed_at')->nullable()->after('disbursement_override');
            $table->foreignId('cash_disbursed_by')->nullable()->after('cash_disbursed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hrm_payroll_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_disbursed_by');
            $table->dropColumn([
                'bank_pay_amount',
                'cash_pay_amount',
                'disbursement_override',
                'cash_disbursed_at',
            ]);
        });

        Schema::table('hrm_salary_structures', function (Blueprint $table) {
            $table->dropColumn('bank_disbursement_amount');
        });
    }
};
