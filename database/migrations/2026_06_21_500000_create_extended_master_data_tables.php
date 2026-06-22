<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function masterTable(Blueprint $table): void
    {
        $table->id();
        $table->string('code', 20)->unique();
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    }

    public function up(): void
    {
        Schema::create('order_types', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('shipment_modes', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('shipout_conditions', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('shipment_statuses', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('fabric_categories', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('sustainabilities', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('price_statuses', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('order_statuses', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('short_order_statuses', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('yarn_statuses', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('woven_statuses', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('trims_statuses', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('accessories_statuses', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('sample_statuses', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('garment_production_statuses', fn (Blueprint $t) => $this->masterTable($t));
        Schema::create('payment_statuses', fn (Blueprint $t) => $this->masterTable($t));

        Schema::create('supplier_types', fn (Blueprint $t) => $this->masterTable($t));

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->foreignId('supplier_type_id')->constrained()->cascadeOnDelete();
            $table->string('company')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('supplier_types');
        Schema::dropIfExists('payment_statuses');
        Schema::dropIfExists('garment_production_statuses');
        Schema::dropIfExists('sample_statuses');
        Schema::dropIfExists('accessories_statuses');
        Schema::dropIfExists('trims_statuses');
        Schema::dropIfExists('woven_statuses');
        Schema::dropIfExists('yarn_statuses');
        Schema::dropIfExists('short_order_statuses');
        Schema::dropIfExists('order_statuses');
        Schema::dropIfExists('price_statuses');
        Schema::dropIfExists('sustainabilities');
        Schema::dropIfExists('fabric_categories');
        Schema::dropIfExists('shipment_statuses');
        Schema::dropIfExists('shipout_conditions');
        Schema::dropIfExists('shipment_modes');
        Schema::dropIfExists('order_types');
    }
};
