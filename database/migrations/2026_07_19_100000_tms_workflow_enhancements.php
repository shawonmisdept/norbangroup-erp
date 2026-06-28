<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tms_drivers', function (Blueprint $table) {
            $table->foreignId('default_vehicle_id')->nullable()->after('employee_id')
                ->constrained('tms_vehicles')->nullOnDelete();
        });

        Schema::table('tms_transport_requests', function (Blueprint $table) {
            $table->foreignId('trip_log_id')->nullable()->after('driver_id')
                ->constrained('tms_trip_logs')->nullOnDelete();
        });

        Schema::table('tms_trip_logs', function (Blueprint $table) {
            $table->unsignedSmallInteger('total_passengers')->default(1)->after('driver_id');
        });

        Schema::create('tms_daily_odometer_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('tms_vehicles')->cascadeOnDelete();
            $table->date('log_date');
            $table->decimal('morning_km', 10, 2)->nullable();
            $table->decimal('evening_km', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('morning_entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('evening_entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['vehicle_id', 'log_date']);
            $table->index(['factory_id', 'log_date']);
        });

        foreach (DB::table('tms_trip_logs')->whereNotNull('transport_request_id')->get() as $log) {
            DB::table('tms_transport_requests')
                ->where('id', $log->transport_request_id)
                ->update(['trip_log_id' => $log->id]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tms_daily_odometer_logs');

        Schema::table('tms_trip_logs', function (Blueprint $table) {
            $table->dropColumn('total_passengers');
        });

        Schema::table('tms_transport_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trip_log_id');
        });

        Schema::table('tms_drivers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_vehicle_id');
        });
    }
};
