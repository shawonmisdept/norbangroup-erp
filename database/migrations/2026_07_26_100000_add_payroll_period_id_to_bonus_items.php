<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hrm_bonus_items')) {
            return;
        }

        Schema::table('hrm_bonus_items', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_bonus_items', 'payroll_period_id')) {
                $table->foreignId('payroll_period_id')->nullable()->after('bonus_amount')
                    ->constrained('hrm_payroll_periods')->nullOnDelete();
            }
        });

        Schema::table('hrm_performance_bonus_items', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_performance_bonus_items', 'payroll_period_id')) {
                $table->foreignId('payroll_period_id')->nullable()->after('final_amount')
                    ->constrained('hrm_payroll_periods')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('hrm_bonus_items') && Schema::hasColumn('hrm_bonus_items', 'payroll_period_id')) {
            Schema::table('hrm_bonus_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('payroll_period_id');
            });
        }

        if (Schema::hasTable('hrm_performance_bonus_items') && Schema::hasColumn('hrm_performance_bonus_items', 'payroll_period_id')) {
            Schema::table('hrm_performance_bonus_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('payroll_period_id');
            });
        }
    }
};
