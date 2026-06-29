<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tms_settings', function (Blueprint $table) {
            $table->decimal('company_night_bill', 10, 2)->default(120)->after('ot_basis');
            $table->decimal('company_holiday_duty_bill', 10, 2)->default(320)->after('company_night_bill');
            $table->decimal('rental_ot_hourly_rate', 10, 2)->default(120)->after('company_holiday_duty_bill');
            $table->decimal('rental_km_rate', 10, 2)->default(12)->after('rental_ot_hourly_rate');
            $table->json('weekend_days')->nullable()->after('rental_km_rate');
        });

        Schema::create('tms_rental_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('mobile', 32)->nullable();
            $table->decimal('rental_km_rate', 10, 2)->nullable();
            $table->string('status', 16)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['factory_id', 'name']);
            $table->index(['factory_id', 'status']);
        });

        Schema::table('tms_vehicles', function (Blueprint $table) {
            $table->foreignId('rental_vendor_id')->nullable()->after('status')
                ->constrained('tms_rental_vendors')->nullOnDelete();
            $table->decimal('rental_km_rate', 10, 2)->nullable()->after('rental_vendor_id');
        });

        Schema::table('tms_vehicles', function (Blueprint $table) {
            $table->dropColumn(['rental_company', 'rental_amount']);
        });

        Schema::table('tms_trip_logs', function (Blueprint $table) {
            $table->decimal('rental_km_rate', 10, 2)->nullable()->after('ot_end_at');
            $table->decimal('rental_charge_amount', 12, 2)->default(0)->after('rental_km_rate');
        });

        Schema::create('tms_rental_vehicle_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_log_id')->constrained('tms_trip_logs')->cascadeOnDelete();
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

            $table->unique('trip_log_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tms_rental_vehicle_charges');

        Schema::table('tms_trip_logs', function (Blueprint $table) {
            $table->dropColumn(['rental_km_rate', 'rental_charge_amount']);
        });

        Schema::table('tms_vehicles', function (Blueprint $table) {
            $table->string('rental_company')->nullable()->after('status');
            $table->decimal('rental_amount', 12, 2)->nullable()->after('rental_company');
        });

        Schema::table('tms_vehicles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rental_vendor_id');
            $table->dropColumn('rental_km_rate');
        });

        Schema::dropIfExists('tms_rental_vendors');

        Schema::table('tms_settings', function (Blueprint $table) {
            $table->dropColumn([
                'company_night_bill',
                'company_holiday_duty_bill',
                'rental_ot_hourly_rate',
                'rental_km_rate',
                'weekend_days',
            ]);
        });
    }
};
