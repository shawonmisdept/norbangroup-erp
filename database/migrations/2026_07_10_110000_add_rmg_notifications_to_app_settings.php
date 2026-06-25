<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->boolean('notify_popup_hrm_worker_transfer')->default(true)->after('notify_popup_hrm_recruitment');
            $table->boolean('notify_popup_hrm_gate_pass')->default(true)->after('notify_popup_hrm_worker_transfer');
            $table->boolean('notify_popup_hrm_proxy_punch')->default(true)->after('notify_popup_hrm_gate_pass');
            $table->boolean('notify_popup_hrm_manpower_variance')->default(true)->after('notify_popup_hrm_proxy_punch');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'notify_popup_hrm_worker_transfer',
                'notify_popup_hrm_gate_pass',
                'notify_popup_hrm_proxy_punch',
                'notify_popup_hrm_manpower_variance',
            ]);
        });
    }
};
