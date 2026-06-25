<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_recruitment_interviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->foreign('application_id', 'hrm_recruit_interviews_app_fk')
                ->references('id')->on('hrm_recruitment_applications')->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('location')->nullable();
            $table->string('interview_type', 20)->default('in_person');
            $table->string('result', 20)->default('pending');
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('panel_notes')->nullable();
            $table->foreignId('scheduled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'scheduled_at'], 'hrm_recruit_interviews_app_date_idx');
        });

        Schema::table('hrm_recruitment_applications', function (Blueprint $table) {
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_recruitment_applications', function (Blueprint $table) {
            $table->dropColumn('phone_verified_at');
        });

        Schema::dropIfExists('hrm_recruitment_interviews');
    }
};
