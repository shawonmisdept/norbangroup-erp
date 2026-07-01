<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->boolean('notify_popup_tms')->default(true)->after('notify_popup_hrm_performance');
            $table->boolean('notify_popup_tms_request_submitted')->default(true)->after('notify_popup_tms');
            $table->boolean('notify_popup_tms_request_approved')->default(true)->after('notify_popup_tms_request_submitted');
            $table->boolean('notify_popup_tms_request_rejected')->default(true)->after('notify_popup_tms_request_approved');
            $table->boolean('notify_popup_tms_request_cancelled')->default(true)->after('notify_popup_tms_request_rejected');
            $table->boolean('notify_popup_tms_trip_started')->default(true)->after('notify_popup_tms_request_cancelled');
            $table->boolean('notify_popup_tms_trip_completed')->default(true)->after('notify_popup_tms_trip_started');
            $table->boolean('notify_popup_tms_ot_pending')->default(true)->after('notify_popup_tms_trip_completed');
            $table->boolean('notify_popup_tms_odometer_reminder')->default(true)->after('notify_popup_tms_ot_pending');
            $table->boolean('notify_sms_tms')->default(false)->after('notify_popup_tms_odometer_reminder');
            $table->boolean('notify_whatsapp_tms')->default(false)->after('notify_sms_tms');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'notify_popup_tms',
                'notify_popup_tms_request_submitted',
                'notify_popup_tms_request_approved',
                'notify_popup_tms_request_rejected',
                'notify_popup_tms_request_cancelled',
                'notify_popup_tms_trip_started',
                'notify_popup_tms_trip_completed',
                'notify_popup_tms_ot_pending',
                'notify_popup_tms_odometer_reminder',
                'notify_sms_tms',
                'notify_whatsapp_tms',
            ]);
        });
    }
};
