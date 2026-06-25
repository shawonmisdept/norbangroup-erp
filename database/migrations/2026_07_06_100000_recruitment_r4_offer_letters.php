<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_recruitment_offer_letters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->foreign('application_id', 'hrm_recruit_offer_app_fk')
                ->references('id')->on('hrm_recruitment_applications')->cascadeOnDelete();
            $table->string('reference_no', 30)->unique();
            $table->text('content');
            $table->decimal('offered_salary', 12, 2)->nullable();
            $table->date('joining_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_recruitment_offer_letters');
    }
};
