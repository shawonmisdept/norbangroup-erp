<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_job_postings', function (Blueprint $table) {
            $table->text('employment_status')->nullable()->after('responsibilities');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_job_postings', function (Blueprint $table) {
            $table->dropColumn('employment_status');
        });
    }
};
