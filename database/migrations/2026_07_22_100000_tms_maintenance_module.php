<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tms_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('tms_vehicles')->cascadeOnDelete();
            $table->date('service_date');
            $table->decimal('odometer_km', 10, 2)->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('service_type', 24)->default('routine');
            $table->text('description')->nullable();
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('parts_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->string('paid_by', 16)->default('company');
            $table->string('status', 16)->default('open');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['factory_id', 'service_date']);
            $table->index(['vehicle_id', 'status']);
        });

        Schema::create('tms_maintenance_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_log_id')->constrained('tms_maintenance_logs')->cascadeOnDelete();
            $table->string('part_name');
            $table->decimal('quantity', 10, 3)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tms_maintenance_parts');
        Schema::dropIfExists('tms_maintenance_logs');
    }
};
