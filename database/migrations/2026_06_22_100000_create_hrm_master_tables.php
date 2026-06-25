<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_buildings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['factory_id', 'name']);
        });

        Schema::create('hrm_floors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained('hrm_buildings')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('floor_number')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['building_id', 'name']);
        });

        Schema::create('hrm_lines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('floor_id')->constrained('hrm_floors')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['floor_id', 'name']);
        });

        Schema::create('hrm_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->boolean('is_night')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['factory_id', 'name']);
        });

        Schema::create('hrm_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('date');
            $table->boolean('is_optional')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['factory_id', 'date', 'name']);
        });

        Schema::create('hrm_worker_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hrm_employment_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hrm_leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->boolean('is_paid')->default(true);
            $table->unsignedSmallInteger('max_days_per_year')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hrm_biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('device_serial')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('adms_url')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['factory_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_biometric_devices');
        Schema::dropIfExists('hrm_leave_types');
        Schema::dropIfExists('hrm_employment_types');
        Schema::dropIfExists('hrm_worker_categories');
        Schema::dropIfExists('hrm_holidays');
        Schema::dropIfExists('hrm_shifts');
        Schema::dropIfExists('hrm_lines');
        Schema::dropIfExists('hrm_floors');
        Schema::dropIfExists('hrm_buildings');
    }
};
