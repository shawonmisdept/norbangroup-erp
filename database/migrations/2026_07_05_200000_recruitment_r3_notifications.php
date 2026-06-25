<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->boolean('notify_popup_hrm_recruitment')->default(true)->after('notify_mail_hrm_payslip');
            $table->boolean('notify_mail_hrm_recruitment_candidate')->default(true)->after('notify_popup_hrm_recruitment');
            $table->boolean('notify_sms_hrm_recruitment')->default(false)->after('notify_mail_hrm_recruitment_candidate');
        });

        Schema::table('hrm_recruitment_interviews', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_recruitment_interviews', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });

        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'notify_popup_hrm_recruitment',
                'notify_mail_hrm_recruitment_candidate',
                'notify_sms_hrm_recruitment',
            ]);
        });
    }
};
