<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('Norbangroup');
            $table->string('app_tagline')->nullable()->default('Manufacturer');
            $table->string('timezone')->default('Asia/Dhaka');
            $table->string('currency_code', 10)->default('BDT');
            $table->string('currency_symbol', 10)->default('৳');
            $table->string('mail_mailer', 20)->default('log');
            $table->string('mail_host')->nullable();
            $table->unsignedSmallInteger('mail_port')->nullable();
            $table->string('mail_username')->nullable();
            $table->text('mail_password')->nullable();
            $table->string('mail_encryption', 10)->nullable();
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->string('mail_admin_address')->nullable();
            $table->boolean('notify_popup_enabled')->default(true);
            $table->boolean('notify_popup_admin_on_order')->default(true);
            $table->boolean('notify_popup_admin_on_status')->default(true);
            $table->boolean('notify_mail_client_on_order')->default(true);
            $table->boolean('notify_mail_admin_on_order')->default(true);
            $table->boolean('notify_mail_client_on_status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
