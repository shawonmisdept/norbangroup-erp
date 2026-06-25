<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_attendance_raw_punches', function (Blueprint $table) {
            $table->index(['factory_id', 'processed_at', 'punched_at'], 'hrm_punch_factory_processed_idx');
        });

        Schema::table('hrm_attendance_daily_logs', function (Blueprint $table) {
            $table->index(['factory_id', 'attendance_date', 'status'], 'hrm_log_factory_date_status_idx');
        });

        Schema::table('hrm_biometric_sync_logs', function (Blueprint $table) {
            $table->index(['biometric_device_id', 'started_at'], 'hrm_sync_log_device_started_idx');
            $table->index(['status', 'started_at'], 'hrm_sync_log_status_started_idx');
        });

        Schema::table('hrm_biometric_devices', function (Blueprint $table) {
            $table->index(['factory_id', 'last_sync_status'], 'hrm_device_factory_sync_status_idx');
            $table->index(['is_active', 'last_seen_at'], 'hrm_device_active_seen_idx');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_biometric_devices', function (Blueprint $table) {
            $table->dropIndex('hrm_device_factory_sync_status_idx');
            $table->dropIndex('hrm_device_active_seen_idx');
        });

        Schema::table('hrm_biometric_sync_logs', function (Blueprint $table) {
            $table->dropIndex('hrm_sync_log_device_started_idx');
            $table->dropIndex('hrm_sync_log_status_started_idx');
        });

        Schema::table('hrm_attendance_daily_logs', function (Blueprint $table) {
            $table->dropIndex('hrm_log_factory_date_status_idx');
        });

        Schema::table('hrm_attendance_raw_punches', function (Blueprint $table) {
            $table->dropIndex('hrm_punch_factory_processed_idx');
        });
    }
};
