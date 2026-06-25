<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_salary_heads', function (Blueprint $table) {
            $table->string('name_bangla', 120)->nullable()->after('name');
            $table->text('description')->nullable()->after('name_bangla');
            $table->string('sort_code', 20)->nullable()->after('sort_order');
            $table->boolean('is_perquisite')->default(false)->after('is_taxable');
            $table->boolean('is_disburse')->default(true)->after('is_perquisite');
        });

        Schema::table('hrm_salary_grade_details', function (Blueprint $table) {
            $table->string('detail_type', 1)->default('F')->after('salary_head_id');
            $table->boolean('is_fixed')->default(true)->after('detail_type');
            $table->text('formula')->nullable()->after('percentage_of_head_id');
        });

        Schema::table('hrm_salary_structures', function (Blueprint $table) {
            $table->decimal('gross_salary', 12, 2)->default(0)->after('salary_grade_id');
            $table->json('head_amounts')->nullable()->after('gross_salary');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_salary_structures', function (Blueprint $table) {
            $table->dropColumn(['gross_salary', 'head_amounts']);
        });

        Schema::table('hrm_salary_grade_details', function (Blueprint $table) {
            $table->dropColumn(['detail_type', 'is_fixed', 'formula']);
        });

        Schema::table('hrm_salary_heads', function (Blueprint $table) {
            $table->dropColumn(['name_bangla', 'description', 'sort_code', 'is_perquisite', 'is_disburse']);
        });
    }
};
