<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tms_settings', function (Blueprint $table) {
            $table->boolean('gps_tracking_enabled')->default(false)->after('weekend_days');
            $table->string('gps_provider', 32)->default('none')->after('gps_tracking_enabled');
        });

        Schema::create('tms_gps_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('tms_vehicles')->cascadeOnDelete();
            $table->foreignId('trip_log_id')->nullable()->constrained('tms_trip_logs')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed_kmh', 8, 2)->nullable();
            $table->decimal('heading', 6, 2)->nullable();
            $table->decimal('accuracy_m', 8, 2)->nullable();
            $table->string('source', 24)->default('stub');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['vehicle_id', 'recorded_at']);
            $table->index(['trip_log_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tms_gps_positions');

        Schema::table('tms_settings', function (Blueprint $table) {
            $table->dropColumn(['gps_tracking_enabled', 'gps_provider']);
        });
    }
};
