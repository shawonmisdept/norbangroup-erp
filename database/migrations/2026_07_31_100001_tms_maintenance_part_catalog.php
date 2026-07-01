<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tms_maintenance_part_catalog', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->string('name');
            $table->string('unit', 16)->nullable();
            $table->decimal('default_unit_price', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['factory_id', 'name']);
        });

        Schema::table('tms_maintenance_items', function (Blueprint $table) {
            $table->foreignId('part_catalog_id')
                ->nullable()
                ->after('maintenance_bill_id')
                ->constrained('tms_maintenance_part_catalog')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tms_maintenance_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('part_catalog_id');
        });

        Schema::dropIfExists('tms_maintenance_part_catalog');
    }
};
