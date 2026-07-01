<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->string('whatsapp_custom_url')->nullable()->after('whatsapp_business_account_id');
            $table->string('whatsapp_sender_id', 64)->nullable()->after('whatsapp_custom_url');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_custom_url', 'whatsapp_sender_id']);
        });
    }
};
