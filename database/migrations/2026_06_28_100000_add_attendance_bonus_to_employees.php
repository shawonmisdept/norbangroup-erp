<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->boolean('attendance_bonus_enabled')->default(false)->after('late_acceptance_enabled');
            $table->decimal('attendance_bonus_amount', 10, 2)->default(0)->after('attendance_bonus_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->dropColumn(['attendance_bonus_enabled', 'attendance_bonus_amount']);
        });
    }
};
