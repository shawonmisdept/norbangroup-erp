<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('worker_category_id')->nullable()->constrained('hrm_worker_categories')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->unsignedSmallInteger('slots')->default(1);
            $table->unsignedSmallInteger('openings_filled')->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['factory_id', 'status'], 'hrm_job_postings_factory_status_idx');
        });

        Schema::create('hrm_recruitment_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no', 30)->unique();
            $table->foreignId('job_posting_id')->constrained('hrm_job_postings')->cascadeOnDelete();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20)->default('online');
            $table->string('status', 20)->default('applied');
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nid_number', 30)->nullable();
            $table->text('present_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('nid_document_path')->nullable();
            $table->json('education_history')->nullable();
            $table->json('employment_history')->nullable();
            $table->decimal('expected_salary', 12, 2)->nullable();
            $table->string('referral_source')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('converted_employee_id')->nullable()->constrained('hrm_employees')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->index(['factory_id', 'status'], 'hrm_recruitment_apps_factory_status_idx');
            $table->index(['job_posting_id', 'phone'], 'hrm_recruitment_apps_posting_phone_idx');
            $table->index(['status', 'applied_at'], 'hrm_recruitment_apps_status_applied_idx');
        });

        Schema::create('hrm_recruitment_application_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->foreign('application_id', 'hrm_recruit_app_logs_app_fk')
                ->references('id')->on('hrm_recruitment_applications')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_recruitment_application_logs');
        Schema::dropIfExists('hrm_recruitment_applications');
        Schema::dropIfExists('hrm_job_postings');
    }
};
