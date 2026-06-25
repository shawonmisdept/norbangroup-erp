<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->boolean('notify_popup_hrm_late_acceptance')->default(true)->after('notify_mail_client_on_status');
            $table->boolean('notify_popup_hrm_unmapped_punch')->default(true)->after('notify_popup_hrm_late_acceptance');
            $table->boolean('notify_popup_hrm_manual_punch')->default(false)->after('notify_popup_hrm_unmapped_punch');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'notify_popup_hrm_late_acceptance',
                'notify_popup_hrm_unmapped_punch',
                'notify_popup_hrm_manual_punch',
            ]);
        });
    }
};
