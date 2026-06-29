<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tms_vehicles', function (Blueprint $table) {
            $table->foreignId('allocated_employee_id')->nullable()->after('maintenance_covered_by')
                ->constrained('hrm_employees')->nullOnDelete();
        });

        if (Schema::hasColumn('tms_vehicles', 'allocated_user')) {
            Schema::table('tms_vehicles', function (Blueprint $table) {
                $table->dropColumn('allocated_user');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tms_vehicles', function (Blueprint $table) {
            $table->string('allocated_user')->nullable()->after('maintenance_covered_by');
            $table->dropConstrainedForeignId('allocated_employee_id');
        });
    }
};
