<?php

use App\Models\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_statuses')) {
            return;
        }

        OrderStatus::firstOrCreate(
            ['name' => 'Commercial Quote'],
            ['is_active' => true],
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('order_statuses')) {
            OrderStatus::where('name', 'Commercial Quote')->delete();
        }
    }
};
