<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('ref_code', 20)->unique();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email');
            $table->string('phone', 30);
            $table->string('item_name');
            $table->unsignedInteger('quantity')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', [
                'New',
                'Under Review',
                'Quoted',
                'Approved',
                'In Production',
                'Shipped',
                'Closed',
                'Cancelled',
            ])->default('New');
            $table->json('techpack_files')->nullable();
            $table->json('artwork_files')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
