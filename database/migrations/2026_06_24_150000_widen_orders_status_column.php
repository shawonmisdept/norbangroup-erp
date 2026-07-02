<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `orders` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'New'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders') || Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `orders` MODIFY `status` ENUM(
            'New',
            'Under Review',
            'Quoted',
            'Approved',
            'In Production',
            'Shipped',
            'Closed',
            'Cancelled'
        ) NOT NULL DEFAULT 'New'");
    }
};
