<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tms_driver_ot_rate_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('tms_drivers')->cascadeOnDelete();
            $table->decimal('ot_rate', 10, 2);
            $table->date('effective_from')->nullable();
            $table->boolean('is_overtime_active')->default(true);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tms_driver_ot_rate_logs');
    }
};
