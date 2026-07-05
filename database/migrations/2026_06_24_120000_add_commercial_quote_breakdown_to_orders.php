<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('quote_garment_type', 20)->nullable();
            $table->string('quote_basis', 10)->nullable();
            $table->string('quote_currency', 3)->default('BDT');
            $table->decimal('quote_price_per_pc', 14, 4)->nullable();
            $table->json('quote_breakdown')->nullable();
            $table->unsignedSmallInteger('quote_lead_time_days')->nullable();
            $table->date('quote_valid_until')->nullable();
            $table->string('quote_payment_terms', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'quote_garment_type',
                'quote_basis',
                'quote_currency',
                'quote_price_per_pc',
                'quote_breakdown',
                'quote_lead_time_days',
                'quote_valid_until',
                'quote_payment_terms',
            ]);
        });
    }
};
