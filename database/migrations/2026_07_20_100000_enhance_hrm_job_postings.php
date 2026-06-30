<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_job_postings', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false)->after('status');
            $table->unsignedInteger('page_views')->default(0)->after('is_internal');
            $table->string('title_bn')->nullable()->after('title');
            $table->text('description_bn')->nullable()->after('description');
            $table->string('meta_description', 500)->nullable()->after('benefits');
            $table->string('shift_type', 20)->nullable()->after('worker_category_id');
            $table->unsignedTinyInteger('min_age')->nullable()->after('shift_type');
            $table->unsignedTinyInteger('max_age')->nullable()->after('min_age');
            $table->string('required_gender', 10)->nullable()->after('max_age');
            $table->boolean('rehire_eligible')->default(false)->after('required_gender');
            $table->timestamp('approved_at')->nullable()->after('published_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->string('template_key', 50)->nullable()->after('created_by');
        });

        Schema::create('hrm_job_posting_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('hrm_job_postings')->cascadeOnDelete();
            $table->string('action', 40);
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['job_posting_id', 'created_at'], 'hrm_job_posting_logs_posting_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_job_posting_logs');

        Schema::table('hrm_job_postings', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'is_internal',
                'page_views',
                'title_bn',
                'description_bn',
                'meta_description',
                'shift_type',
                'min_age',
                'max_age',
                'required_gender',
                'rehire_eligible',
                'approved_at',
                'approved_by',
                'template_key',
            ]);
        });
    }
};
