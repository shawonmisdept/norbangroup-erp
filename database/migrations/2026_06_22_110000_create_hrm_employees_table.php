<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 30)->unique();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('worker_category_id')->nullable()->constrained('hrm_worker_categories')->nullOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained('hrm_employment_types')->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained('hrm_buildings')->nullOnDelete();
            $table->foreignId('floor_id')->nullable()->constrained('hrm_floors')->nullOnDelete();
            $table->foreignId('line_id')->nullable()->constrained('hrm_lines')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('hrm_shifts')->nullOnDelete();

            $table->string('name');
            $table->string('name_bangla')->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('nid_number', 30)->nullable();
            $table->string('birth_certificate_no', 30)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('present_address')->nullable();
            $table->text('permanent_address')->nullable();

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('emergency_contact_relation')->nullable();

            $table->string('nominee_name')->nullable();
            $table->string('nominee_relation')->nullable();
            $table->string('nominee_nid', 30)->nullable();

            $table->string('biometric_user_id', 50)->nullable();
            $table->string('photo')->nullable();

            $table->date('joining_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['factory_id', 'status']);
            $table->index('nid_number');
            $table->index('phone');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_employees');
    }
};
