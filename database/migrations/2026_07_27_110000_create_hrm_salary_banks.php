<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_salary_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('short_name', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['factory_id', 'code'], 'hrm_salary_bank_factory_code_uq');
            $table->index(['factory_id', 'is_active'], 'hrm_salary_bank_factory_active_idx');
        });

        Schema::table('hrm_salary_structures', function (Blueprint $table) {
            $table->foreignId('salary_bank_id')->nullable()->after('payment_method')->constrained('hrm_salary_banks')->nullOnDelete();
        });

        Schema::table('hrm_payroll_items', function (Blueprint $table) {
            $table->foreignId('salary_bank_id')->nullable()->after('payment_method')->constrained('hrm_salary_banks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hrm_payroll_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('salary_bank_id');
        });

        Schema::table('hrm_salary_structures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('salary_bank_id');
        });

        Schema::dropIfExists('hrm_salary_banks');
    }
};
