<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->string('navbar_logo')->nullable()->after('app_tagline');
            $table->string('frontend_logo')->nullable()->after('navbar_logo');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['navbar_logo', 'frontend_logo']);
        });
    }
};
