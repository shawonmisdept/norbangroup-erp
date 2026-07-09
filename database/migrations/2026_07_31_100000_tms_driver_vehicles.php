<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tms_driver_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('tms_drivers')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('tms_vehicles')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['driver_id', 'vehicle_id']);
            $table->index(['vehicle_id', 'is_primary']);
        });

        foreach (DB::table('tms_drivers')->whereNotNull('default_vehicle_id')->get() as $driver) {
            DB::table('tms_driver_vehicles')->insert([
                'driver_id'   => $driver->id,
                'vehicle_id'  => $driver->default_vehicle_id,
                'is_primary'  => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tms_driver_vehicles');
    }
};
