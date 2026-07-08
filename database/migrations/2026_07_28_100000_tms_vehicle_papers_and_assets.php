<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tms_vehicles', function (Blueprint $table) {
            $table->string('vehicle_category', 32)->nullable()->after('name');
            $table->unsignedSmallInteger('model_year')->nullable()->after('vehicle_category');
            $table->unsignedInteger('engine_cc')->nullable()->after('model_year');
            $table->date('purchase_date')->nullable()->after('engine_cc');
            $table->date('registration_date')->nullable()->after('purchase_date');
            $table->decimal('purchase_value', 14, 2)->nullable()->after('registration_date');
            $table->boolean('is_dedicated')->default(false)->after('purchase_value');
            $table->date('fitness_expires_at')->nullable()->after('is_dedicated');
            $table->date('tax_token_expires_at')->nullable()->after('fitness_expires_at');
            $table->date('insurance_expires_at')->nullable()->after('tax_token_expires_at');
            $table->date('route_permit_expires_at')->nullable()->after('insurance_expires_at');
            $table->string('registration_paper_status', 16)->default('ok')->after('route_permit_expires_at');
            $table->foreignId('primary_driver_id')->nullable()->after('allocated_employee_id')
                ->constrained('tms_drivers')->nullOnDelete();
        });

        Schema::create('tms_vehicle_paper_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('tms_vehicles')->cascadeOnDelete();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->string('paper_type', 32);
            $table->date('previous_expires_at')->nullable();
            $table->date('new_expires_at');
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('document_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('renewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('renewed_at')->useCurrent();
            $table->timestamps();

            $table->index(['vehicle_id', 'paper_type']);
            $table->index(['factory_id', 'renewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tms_vehicle_paper_renewals');

        Schema::table('tms_vehicles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_driver_id');
            $table->dropColumn([
                'vehicle_category',
                'model_year',
                'engine_cc',
                'purchase_date',
                'registration_date',
                'purchase_value',
                'is_dedicated',
                'fitness_expires_at',
                'tax_token_expires_at',
                'insurance_expires_at',
                'route_permit_expires_at',
                'registration_paper_status',
            ]);
        });
    }
};
