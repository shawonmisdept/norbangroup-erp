<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_hr_letter_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('letter_type', 30);
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hrm_issued_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('hrm_hr_letter_templates')->nullOnDelete();
            $table->string('letter_type', 30);
            $table->string('reference_no', 40)->unique();
            $table->longText('content');
            $table->text('notes')->nullable();
            $table->timestamp('issued_at');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['factory_id', 'letter_type'], 'hrm_issued_letters_factory_type_idx');
            $table->index(['employee_id', 'issued_at'], 'hrm_issued_letters_employee_idx');
        });

        Schema::create('hrm_disciplinary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->string('action_type', 30);
            $table->date('incident_date');
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->date('suspension_from')->nullable();
            $table->date('suspension_to')->nullable();
            $table->string('status', 20)->default('open');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['factory_id', 'status'], 'hrm_disciplinary_factory_status_idx');
            $table->index(['employee_id', 'incident_date'], 'hrm_disciplinary_employee_idx');
        });

        Schema::table('hrm_employee_separations', function (Blueprint $table) {
            $table->json('exit_clearance')->nullable()->after('rejection_reason');
            $table->text('exit_interview_notes')->nullable()->after('exit_clearance');
            $table->timestamp('exit_interview_at')->nullable()->after('exit_interview_notes');
        });

        $now = now();
        $templates = [
            [
                'code'        => 'appointment',
                'name'        => 'Appointment Letter',
                'letter_type' => 'appointment',
                'body'        => "Date: {{date}}\n\nTo,\n{{employee_name}}\nEmployee ID: {{employee_code}}\n\nSubject: Appointment Letter\n\nDear {{employee_name}},\n\nWe are pleased to appoint you as {{designation}} in the {{department}} department at {{factory_name}}, effective from {{joining_date}}.\n\nWe welcome you to Norban Group and wish you a successful career with us.\n\nSincerely,\nHuman Resources\n{{factory_name}}",
            ],
            [
                'code'        => 'confirmation',
                'name'        => 'Confirmation Letter',
                'letter_type' => 'confirmation',
                'body'        => "Date: {{date}}\n\nTo,\n{{employee_name}} ({{employee_code}})\n\nSubject: Confirmation of Employment\n\nWe are pleased to confirm your employment with {{factory_name}} as {{designation}} with effect from {{confirmation_date}}.\n\nSincerely,\nHuman Resources",
            ],
            [
                'code'        => 'warning',
                'name'        => 'Warning Letter',
                'letter_type' => 'warning',
                'body'        => "Date: {{date}}\n\nTo,\n{{employee_name}} ({{employee_code}})\nDepartment: {{department}}\n\nSubject: Warning Letter\n\nThis is to inform you that following an review of your conduct/performance, management has decided to issue this written warning. You are advised to improve immediately and comply with company rules.\n\nSincerely,\nHuman Resources\n{{factory_name}}",
            ],
            [
                'code'        => 'experience',
                'name'        => 'Experience Certificate',
                'letter_type' => 'experience',
                'body'        => "Date: {{date}}\n\nTO WHOM IT MAY CONCERN\n\nThis is to certify that {{employee_name}} (Employee ID: {{employee_code}}) was employed with {{factory_name}} as {{designation}} in the {{department}} department from {{joining_date}} to {{last_working_day}}.\n\nDuring employment, {{employee_name}} performed duties satisfactorily. We wish {{employee_name}} success in future endeavours.\n\nSincerely,\nHuman Resources\n{{factory_name}}",
            ],
            [
                'code'        => 'relieving',
                'name'        => 'Relieving Letter',
                'letter_type' => 'relieving',
                'body'        => "Date: {{date}}\n\nTo,\n{{employee_name}} ({{employee_code}})\n\nSubject: Relieving Letter\n\nThis is to confirm that you have been relieved from your duties at {{factory_name}} with effect from {{last_working_day}}. We acknowledge receipt of company assets and clearance as applicable.\n\nWe thank you for your service and wish you the best.\n\nSincerely,\nHuman Resources\n{{factory_name}}",
            ],
            [
                'code'        => 'termination',
                'name'        => 'Termination Letter',
                'letter_type' => 'termination',
                'body'        => "Date: {{date}}\n\nTo,\n{{employee_name}} ({{employee_code}})\n\nSubject: Termination of Employment\n\nWe regret to inform you that your employment with {{factory_name}} is terminated with effect from {{last_working_day}}.\n\nSincerely,\nHuman Resources\n{{factory_name}}",
            ],
        ];

        foreach ($templates as $template) {
            DB::table('hrm_hr_letter_templates')->insert(array_merge($template, [
                'factory_id' => null,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::table('hrm_employee_separations', function (Blueprint $table) {
            $table->dropColumn(['exit_clearance', 'exit_interview_notes', 'exit_interview_at']);
        });

        Schema::dropIfExists('hrm_disciplinary_records');
        Schema::dropIfExists('hrm_issued_letters');
        Schema::dropIfExists('hrm_hr_letter_templates');
    }
};
