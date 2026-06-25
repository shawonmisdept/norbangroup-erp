<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_job_postings', function (Blueprint $table) {
            $table->text('skills_expertise')->nullable()->after('requirements');
            $table->text('responsibilities')->nullable()->after('skills_expertise');
            $table->text('salary_text')->nullable()->after('responsibilities');
            $table->boolean('salary_negotiable')->default(false)->after('salary_text');
            $table->text('benefits')->nullable()->after('salary_negotiable');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_job_postings', function (Blueprint $table) {
            $table->dropColumn([
                'skills_expertise',
                'responsibilities',
                'salary_text',
                'salary_negotiable',
                'benefits',
            ]);
        });
    }
};
