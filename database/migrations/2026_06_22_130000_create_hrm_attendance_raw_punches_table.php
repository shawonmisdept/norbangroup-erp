<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_biometric_devices', function (Blueprint $table) {
            $table->timestamp('last_synced_at')->nullable()->after('is_active');
            $table->string('last_sync_status', 20)->nullable()->after('last_synced_at');
            $table->text('last_sync_message')->nullable()->after('last_sync_status');
        });

        Schema::create('hrm_attendance_raw_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('biometric_device_id')->nullable()->constrained('hrm_biometric_devices')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('hrm_employees')->nullOnDelete();
            $table->string('device_serial', 50)->nullable();
            $table->string('biometric_user_id', 50);
            $table->timestamp('punched_at');
            $table->string('punch_type', 20)->default('unknown');
            $table->string('source', 20);
            $table->string('external_id', 100)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['factory_id', 'punched_at']);
            $table->index(['employee_id', 'punched_at']);
            $table->index(['biometric_user_id', 'punched_at']);
            $table->unique(['biometric_device_id', 'external_id'], 'hrm_raw_punch_device_external_unique');
        });

        Schema::create('hrm_biometric_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biometric_device_id')->constrained('hrm_biometric_devices')->cascadeOnDelete();
            $table->string('status', 20);
            $table->unsignedInteger('records_fetched')->default(0);
            $table->unsignedInteger('records_imported')->default(0);
            $table->unsignedInteger('records_skipped')->default(0);
            $table->text('message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_biometric_sync_logs');
        Schema::dropIfExists('hrm_attendance_raw_punches');
        Schema::table('hrm_biometric_devices', function (Blueprint $table) {
            $table->dropColumn(['last_synced_at', 'last_sync_status', 'last_sync_message']);
        });
    }
};
