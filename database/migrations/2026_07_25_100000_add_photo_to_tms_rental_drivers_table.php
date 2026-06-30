<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tms_rental_drivers', 'photo')) {
            return;
        }

        Schema::table('tms_rental_drivers', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('license_number');
        });
    }

    public function down(): void
    {
        Schema::table('tms_rental_drivers', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
};
