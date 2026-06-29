<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tms_daily_odometer_logs', function (Blueprint $table) {
            $table->dateTime('morning_recorded_at')->nullable()->after('morning_km');
            $table->dateTime('evening_recorded_at')->nullable()->after('evening_km');
            $table->foreignId('morning_entered_by_employee')->nullable()->after('morning_entered_by')
                ->constrained('hrm_employees')->nullOnDelete();
            $table->foreignId('evening_entered_by_employee')->nullable()->after('evening_entered_by')
                ->constrained('hrm_employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tms_daily_odometer_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('morning_entered_by_employee');
            $table->dropConstrainedForeignId('evening_entered_by_employee');
            $table->dropColumn(['morning_recorded_at', 'evening_recorded_at']);
        });
    }
};
