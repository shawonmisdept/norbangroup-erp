<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->string('sms_provider', 30)->default('log')->after('notify_sms_hrm_recruitment');
            $table->text('sms_api_key')->nullable()->after('sms_provider');
            $table->text('sms_api_secret')->nullable()->after('sms_api_key');
            $table->string('sms_sender_id', 20)->nullable()->after('sms_api_secret');
            $table->string('sms_custom_url')->nullable()->after('sms_sender_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'sms_provider',
                'sms_api_key',
                'sms_api_secret',
                'sms_sender_id',
                'sms_custom_url',
            ]);
        });
    }
};
