<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factories', function (Blueprint $table) {
            $table->decimal('attendance_lat', 10, 7)->nullable()->after('is_active');
            $table->decimal('attendance_lng', 10, 7)->nullable()->after('attendance_lat');
            $table->unsignedSmallInteger('attendance_radius_m')->default(200)->after('attendance_lng');
            $table->boolean('mobile_checkin_enabled')->default(true)->after('attendance_radius_m');
        });

        Schema::create('hrm_attendance_gate_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('location', 150)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('qr_token', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['factory_id', 'code']);
        });

        Schema::table('hrm_attendance_raw_punches', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('raw_payload');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('geo_distance_m')->nullable()->after('longitude');
            $table->string('photo_path')->nullable()->after('geo_distance_m');
            $table->foreignId('gate_point_id')->nullable()->after('photo_path')
                ->constrained('hrm_attendance_gate_points')->nullOnDelete();
            $table->foreignId('entered_by_user_id')->nullable()->after('gate_point_id')
                ->constrained('users')->nullOnDelete();
            $table->string('reason', 500)->nullable()->after('entered_by_user_id');
        });

        Schema::table('hrm_biometric_devices', function (Blueprint $table) {
            $table->string('device_model', 80)->nullable()->after('device_serial');
            $table->timestamp('last_seen_at')->nullable()->after('last_sync_message');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_biometric_devices', function (Blueprint $table) {
            $table->dropColumn(['device_model', 'last_seen_at']);
        });

        Schema::table('hrm_attendance_raw_punches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gate_point_id');
            $table->dropConstrainedForeignId('entered_by_user_id');
            $table->dropColumn(['latitude', 'longitude', 'geo_distance_m', 'photo_path', 'reason']);
        });

        Schema::dropIfExists('hrm_attendance_gate_points');

        Schema::table('factories', function (Blueprint $table) {
            $table->dropColumn(['attendance_lat', 'attendance_lng', 'attendance_radius_m', 'mobile_checkin_enabled']);
        });
    }
};
