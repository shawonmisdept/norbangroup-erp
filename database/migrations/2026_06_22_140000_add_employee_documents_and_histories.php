<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->string('nid_document')->nullable()->after('nid_number');
            $table->string('birth_certificate_document')->nullable()->after('birth_certificate_no');
            $table->string('nominee_nid_document')->nullable()->after('nominee_nid');
            $table->string('nominee_photo')->nullable()->after('nominee_nid_document');
        });

        Schema::create('hrm_employee_education_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->string('degree')->nullable();
            $table->string('institution')->nullable();
            $table->string('board_or_university')->nullable();
            $table->string('passing_year', 10)->nullable();
            $table->string('result')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('hrm_employee_employment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->string('designation')->nullable();
            $table->string('department')->nullable();
            $table->date('joining_date')->nullable();
            $table->date('leaving_date')->nullable();
            $table->string('reason_for_leaving')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_employee_employment_histories');
        Schema::dropIfExists('hrm_employee_education_histories');

        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->dropColumn([
                'nid_document',
                'birth_certificate_document',
                'nominee_nid_document',
                'nominee_photo',
            ]);
        });
    }
};
