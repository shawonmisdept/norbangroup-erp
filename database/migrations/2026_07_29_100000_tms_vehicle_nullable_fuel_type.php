<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tms_vehicles', function (Blueprint $table) {
            $table->string('fuel_type', 16)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tms_vehicles', function (Blueprint $table) {
            $table->string('fuel_type', 16)->default('petrol')->nullable(false)->change();
        });
    }
};
