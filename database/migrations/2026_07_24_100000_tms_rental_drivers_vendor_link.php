<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tms_rental_drivers', function (Blueprint $table) {
            $table->foreignId('rental_vendor_id')->nullable()->after('license_number')
                ->constrained('tms_rental_vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tms_rental_drivers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rental_vendor_id');
        });
    }
};
