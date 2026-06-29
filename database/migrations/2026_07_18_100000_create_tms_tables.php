<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tms_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->time('office_start')->default('09:00:00');
            $table->time('office_end')->default('17:00:00');
            $table->string('ot_basis', 32)->default('global_office_time');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('factory_id');
        });

        Schema::create('tms_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['factory_id', 'is_active']);
        });

        Schema::create('tms_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->string('name');
            $table->string('reg_number');
            $table->string('type', 16)->default('own');
            $table->string('fuel_type', 16)->default('petrol');
            $table->unsignedSmallInteger('passenger_capacity')->default(4);
            $table->string('status', 16)->default('available');
            $table->string('rental_company')->nullable();
            $table->decimal('rental_amount', 12, 2)->nullable();
            $table->string('fuel_covered_by', 16)->nullable();
            $table->string('maintenance_covered_by', 16)->nullable();
            $table->decimal('last_odometer_km', 10, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['factory_id', 'reg_number']);
            $table->index(['factory_id', 'status']);
        });

        Schema::create('tms_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->string('license_number')->nullable();
            $table->decimal('ot_rate', 10, 2)->default(0);
            $table->boolean('is_overtime_active')->default(true);
            $table->date('ot_rate_effective_from')->nullable();
            $table->string('status', 16)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['factory_id', 'employee_id']);
            $table->index(['factory_id', 'status']);
        });

        Schema::create('tms_transport_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->string('pickup_location');
            $table->foreignId('destination_id')->nullable()->constrained('tms_destinations')->nullOnDelete();
            $table->string('destination_custom')->nullable();
            $table->dateTime('pickup_at');
            $table->string('purpose');
            $table->unsignedSmallInteger('passenger_count')->default(1);
            $table->string('status', 16)->default('pending');
            $table->foreignId('vehicle_id')->nullable()->constrained('tms_vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('tms_drivers')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['factory_id', 'status']);
            $table->index(['employee_id', 'status']);
        });

        Schema::create('tms_trip_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_request_id')->constrained('tms_transport_requests')->cascadeOnDelete();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('tms_vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('tms_drivers')->nullOnDelete();
            $table->decimal('start_km', 10, 2)->nullable();
            $table->decimal('end_km', 10, 2)->nullable();
            $table->decimal('total_km', 10, 2)->nullable();
            $table->timestamp('duty_start_at')->nullable();
            $table->timestamp('duty_end_at')->nullable();
            $table->decimal('ot_hours', 8, 2)->default(0);
            $table->decimal('ot_amount', 12, 2)->default(0);
            $table->timestamp('ot_start_at')->nullable();
            $table->timestamp('ot_end_at')->nullable();
            $table->string('trip_status', 16)->default('not_started');
            $table->timestamps();

            $table->index(['driver_id', 'trip_status']);
            $table->index(['vehicle_id', 'trip_status']);
        });

        Schema::create('tms_fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('tms_vehicles')->cascadeOnDelete();
            $table->foreignId('trip_log_id')->nullable()->constrained('tms_trip_logs')->nullOnDelete();
            $table->string('fuel_type', 16);
            $table->decimal('quantity', 10, 3);
            $table->string('unit', 16)->default('litre');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('amount', 12, 2);
            $table->string('receipt_number')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('paid_by', 16)->default('company');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['factory_id', 'vehicle_id']);
        });

        Schema::create('tms_transport_request_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_request_id')->constrained('tms_transport_requests')->cascadeOnDelete();
            $table->string('from_status', 16)->nullable();
            $table->string('to_status', 16);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('changed_by_employee_id')->nullable()->constrained('hrm_employees')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('tms_driver_overtime_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_log_id')->constrained('tms_trip_logs')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('tms_drivers')->nullOnDelete();
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
        Schema::dropIfExists('tms_driver_overtime_payments');
        Schema::dropIfExists('tms_transport_request_histories');
        Schema::dropIfExists('tms_fuel_logs');
        Schema::dropIfExists('tms_trip_logs');
        Schema::dropIfExists('tms_transport_requests');
        Schema::dropIfExists('tms_drivers');
        Schema::dropIfExists('tms_vehicles');
        Schema::dropIfExists('tms_destinations');
        Schema::dropIfExists('tms_settings');
    }
};
