<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tms_maintenance_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('tms_vehicles')->cascadeOnDelete();
            $table->string('bill_no');
            $table->date('bill_date');
            $table->string('workshop_name');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('paid_by', 16)->default('company');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('bill_no');
            $table->index(['vehicle_id', 'bill_date']);
            $table->index(['factory_id', 'bill_date']);
            $table->index('workshop_name');
        });

        Schema::create('tms_maintenance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_bill_id')->constrained('tms_maintenance_bills')->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('quantity', 10, 3)->nullable();
            $table->string('unit', 16)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        if (Schema::hasTable('tms_maintenance_logs')) {
            $this->migrateLegacyLogs();
            Schema::dropIfExists('tms_maintenance_parts');
            Schema::dropIfExists('tms_maintenance_logs');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tms_maintenance_items');
        Schema::dropIfExists('tms_maintenance_bills');
    }

    private function migrateLegacyLogs(): void
    {
        $logs = DB::table('tms_maintenance_logs')->orderBy('id')->get();

        foreach ($logs as $log) {
            $billNo = 'LEG-' . str_pad((string) $log->id, 6, '0', STR_PAD_LEFT);

            $billId = DB::table('tms_maintenance_bills')->insertGetId([
                'factory_id'   => $log->factory_id,
                'vehicle_id'   => $log->vehicle_id,
                'bill_no'      => $billNo,
                'bill_date'    => $log->service_date,
                'workshop_name'=> $log->vendor_name ?: 'Legacy',
                'total_amount' => $log->total_cost,
                'paid_by'      => $log->paid_by,
                'notes'        => $log->notes,
                'created_by'   => $log->created_by,
                'updated_by'   => $log->updated_by,
                'created_at'   => $log->created_at,
                'updated_at'   => $log->updated_at,
            ]);

            $sort = 0;

            if ((float) $log->labor_cost > 0) {
                DB::table('tms_maintenance_items')->insert([
                    'maintenance_bill_id' => $billId,
                    'item_name'           => 'Service Charge',
                    'quantity'            => null,
                    'unit'                => null,
                    'amount'              => $log->labor_cost,
                    'sort_order'          => $sort++,
                    'created_at'          => $log->created_at,
                    'updated_at'          => $log->updated_at,
                ]);
            }

            $parts = DB::table('tms_maintenance_parts')
                ->where('maintenance_log_id', $log->id)
                ->orderBy('id')
                ->get();

            foreach ($parts as $part) {
                DB::table('tms_maintenance_items')->insert([
                    'maintenance_bill_id' => $billId,
                    'item_name'           => $part->part_name,
                    'quantity'            => $part->quantity,
                    'unit'                => null,
                    'amount'              => $part->amount,
                    'sort_order'          => $sort++,
                    'created_at'          => $part->created_at,
                    'updated_at'          => $part->updated_at,
                ]);
            }
        }
    }
};
