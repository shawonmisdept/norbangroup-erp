<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kb_articles', function (Blueprint $table) {
            $table->longText('purpose_en')->nullable()->after('summary_bn');
            $table->longText('purpose_bn')->nullable()->after('purpose_en');
            $table->longText('audience_en')->nullable()->after('purpose_bn');
            $table->longText('audience_bn')->nullable()->after('audience_en');
            $table->longText('usage_rules_en')->nullable()->after('audience_bn');
            $table->longText('usage_rules_bn')->nullable()->after('usage_rules_en');
        });
    }

    public function down(): void
    {
        Schema::table('kb_articles', function (Blueprint $table) {
            $table->dropColumn([
                'purpose_en',
                'purpose_bn',
                'audience_en',
                'audience_bn',
                'usage_rules_en',
                'usage_rules_bn',
            ]);
        });
    }
};
