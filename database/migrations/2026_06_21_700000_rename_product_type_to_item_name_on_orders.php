<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'product_type')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('product_type', 'item_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('item_name', 'product_type');
        });
    }
};
