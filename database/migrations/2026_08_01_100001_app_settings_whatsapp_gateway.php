<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->string('whatsapp_provider', 30)->default('log')->after('notify_whatsapp_tms');
            $table->text('whatsapp_api_token')->nullable()->after('whatsapp_provider');
            $table->string('whatsapp_phone_number_id', 64)->nullable()->after('whatsapp_api_token');
            $table->string('whatsapp_business_account_id', 64)->nullable()->after('whatsapp_phone_number_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_provider',
                'whatsapp_api_token',
                'whatsapp_phone_number_id',
                'whatsapp_business_account_id',
            ]);
        });
    }
};
