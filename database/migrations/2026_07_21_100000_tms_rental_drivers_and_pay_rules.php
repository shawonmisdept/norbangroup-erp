<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tms_rental_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->string('name');
            $table->string('mobile', 32)->nullable();
            $table->string('nid_number', 64)->nullable();
            $table->string('license_number', 64)->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('vendor_contact', 64)->nullable();
            $table->foreignId('default_vehicle_id')->nullable()->constrained('tms_vehicles')->nullOnDelete();
            $table->string('status', 16)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['factory_id', 'status']);
        });

        Schema::table('tms_transport_requests', function (Blueprint $table) {
            $table->foreignId('rental_driver_id')->nullable()->after('driver_id')
                ->constrained('tms_rental_drivers')->nullOnDelete();
        });

        Schema::table('tms_trip_logs', function (Blueprint $table) {
            $table->foreignId('rental_driver_id')->nullable()->after('driver_id')
                ->constrained('tms_rental_drivers')->nullOnDelete();
            $table->string('driver_type', 16)->nullable()->after('rental_driver_id');
            $table->string('bill_type', 24)->default('none')->after('ot_end_at');
            $table->decimal('night_bill_amount', 12, 2)->default(0)->after('bill_type');
            $table->decimal('holiday_duty_amount', 12, 2)->default(0)->after('night_bill_amount');
            $table->decimal('ot_hourly_amount', 12, 2)->default(0)->after('ot_hours');
            $table->decimal('total_driver_pay', 12, 2)->default(0)->after('ot_amount');
        });

        Schema::table('tms_driver_overtime_payments', function (Blueprint $table) {
            $table->foreignId('rental_driver_id')->nullable()->after('driver_id')
                ->constrained('tms_rental_drivers')->nullOnDelete();
            $table->json('payment_breakdown')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('tms_driver_overtime_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rental_driver_id');
            $table->dropColumn('payment_breakdown');
        });

        Schema::table('tms_trip_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rental_driver_id');
            $table->dropColumn([
                'driver_type', 'bill_type', 'night_bill_amount',
                'holiday_duty_amount', 'ot_hourly_amount', 'total_driver_pay',
            ]);
        });

        Schema::table('tms_transport_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rental_driver_id');
        });

        Schema::dropIfExists('tms_rental_drivers');
    }
};
