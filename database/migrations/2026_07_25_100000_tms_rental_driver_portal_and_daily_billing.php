<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tms_rental_driver_portal_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_driver_id')->unique()->constrained('tms_rental_drivers')->cascadeOnDelete();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::table('tms_daily_odometer_logs', function (Blueprint $table) {
            $table->foreignId('morning_entered_by_rental_driver')->nullable()->after('evening_entered_by_employee')
                ->constrained('tms_rental_drivers')->nullOnDelete();
            $table->foreignId('evening_entered_by_rental_driver')->nullable()->after('morning_entered_by_rental_driver')
                ->constrained('tms_rental_drivers')->nullOnDelete();
        });

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::rename('tms_rental_vehicle_charges', 'tms_rental_vehicle_charges_legacy');

            Schema::create('tms_rental_vehicle_charges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trip_log_id')->nullable()->constrained('tms_trip_logs')->nullOnDelete();
                $table->foreignId('odometer_log_id')->nullable()->constrained('tms_daily_odometer_logs')->nullOnDelete();
                $table->date('log_date')->nullable();
                $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
                $table->foreignId('vehicle_id')->constrained('tms_vehicles')->cascadeOnDelete();
                $table->foreignId('rental_vendor_id')->nullable()->constrained('tms_rental_vendors')->nullOnDelete();
                $table->decimal('total_km', 10, 2);
                $table->decimal('km_rate', 10, 2);
                $table->decimal('amount', 12, 2);
                $table->string('payment_status', 16)->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique('odometer_log_id');
            });

            \Illuminate\Support\Facades\DB::statement(
                'INSERT INTO tms_rental_vehicle_charges (id, trip_log_id, factory_id, vehicle_id, rental_vendor_id, total_km, km_rate, amount, payment_status, paid_at, paid_by, created_at, updated_at)
                 SELECT id, trip_log_id, factory_id, vehicle_id, rental_vendor_id, total_km, km_rate, amount, payment_status, paid_at, paid_by, created_at, updated_at
                 FROM tms_rental_vehicle_charges_legacy'
            );

            Schema::drop('tms_rental_vehicle_charges_legacy');
        } else {
            Schema::table('tms_rental_vehicle_charges', function (Blueprint $table) {
                $table->foreignId('odometer_log_id')->nullable()->after('trip_log_id')
                    ->constrained('tms_daily_odometer_logs')->nullOnDelete();
                $table->date('log_date')->nullable()->after('odometer_log_id');
                $table->unique('odometer_log_id');
            });

            Schema::table('tms_rental_vehicle_charges', function (Blueprint $table) {
                $table->dropForeign(['trip_log_id']);
                $table->dropUnique(['trip_log_id']);
                $table->unsignedBigInteger('trip_log_id')->nullable()->change();
                $table->foreign('trip_log_id')->references('id')->on('tms_trip_logs')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('tms_rental_vehicle_charges', function (Blueprint $table) {
                $table->dropForeign(['trip_log_id']);
                $table->unsignedBigInteger('trip_log_id')->nullable(false)->change();
                $table->unique('trip_log_id');
                $table->foreign('trip_log_id')->references('id')->on('tms_trip_logs')->cascadeOnDelete();
            });
        }

        Schema::table('tms_rental_vehicle_charges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('odometer_log_id');
            $table->dropColumn('log_date');
        });

        Schema::table('tms_daily_odometer_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evening_entered_by_rental_driver');
            $table->dropConstrainedForeignId('morning_entered_by_rental_driver');
        });

        Schema::dropIfExists('tms_rental_driver_portal_users');
    }
};
