<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->foreignId('reporting_to_id')
                ->nullable()
                ->after('shift_id')
                ->constrained('hrm_employees')
                ->nullOnDelete();
        });

        Schema::table('hrm_leave_approvals', function (Blueprint $table) {
            $table->foreignId('approver_employee_id')
                ->nullable()
                ->after('step_label')
                ->constrained('hrm_employees')
                ->nullOnDelete();

            $table->foreignId('acted_by_employee_id')
                ->nullable()
                ->after('acted_by')
                ->constrained('hrm_employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hrm_leave_approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acted_by_employee_id');
            $table->dropConstrainedForeignId('approver_employee_id');
        });

        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reporting_to_id');
        });
    }
};
