<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_payroll_items', function (Blueprint $table) {
            $table->decimal('ot_hours', 8, 2)->default(0)->after('half_days');
            $table->decimal('ot_amount', 12, 2)->default(0)->after('ot_hours');
            $table->json('head_breakdown')->nullable()->after('net_pay');
            $table->timestamp('payslip_sent_at')->nullable()->after('head_breakdown');
        });

        Schema::table('hrm_payroll_periods', function (Blueprint $table) {
            $table->timestamp('payslips_sent_at')->nullable()->after('frozen_at');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_payroll_periods', function (Blueprint $table) {
            $table->dropColumn('payslips_sent_at');
        });

        Schema::table('hrm_payroll_items', function (Blueprint $table) {
            $table->dropColumn(['ot_hours', 'ot_amount', 'head_breakdown', 'payslip_sent_at']);
        });
    }
};
