<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_modules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('label_en');
            $table->string('label_bn');
            $table->string('view_permission', 80)->nullable();
            $table->string('submodules_config', 120)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kb_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kb_module_id')->constrained('kb_modules')->cascadeOnDelete();
            $table->string('submodule_key', 80)->nullable();
            $table->string('title_en');
            $table->string('title_bn');
            $table->string('summary_en', 500)->nullable();
            $table->string('summary_bn', 500)->nullable();
            $table->longText('body_en')->nullable();
            $table->longText('body_bn')->nullable();
            $table->boolean('is_published')->default(false);
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['kb_module_id', 'submodule_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_articles');
        Schema::dropIfExists('kb_modules');
    }
};
