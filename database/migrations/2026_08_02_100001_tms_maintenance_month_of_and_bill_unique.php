<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tms_maintenance_bills')) {
            return;
        }

        Schema::table('tms_maintenance_bills', function (Blueprint $table) {
            if (! Schema::hasColumn('tms_maintenance_bills', 'month_of')) {
                $table->string('month_of', 32)->nullable()->after('bill_date');
            }
        });

        $this->dropIndexIfExists('tms_maintenance_bills_vehicle_bill_no_unique');

        Schema::table('tms_maintenance_bills', function (Blueprint $table) {
            $table->unique(
                ['vehicle_id', 'bill_no', 'bill_date'],
                'tms_maintenance_bills_vehicle_bill_date_unique'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tms_maintenance_bills')) {
            return;
        }

        $this->dropIndexIfExists('tms_maintenance_bills_vehicle_bill_date_unique');

        Schema::table('tms_maintenance_bills', function (Blueprint $table) {
            if (Schema::hasColumn('tms_maintenance_bills', 'month_of')) {
                $table->dropColumn('month_of');
            }

            $table->unique(
                ['vehicle_id', 'bill_no'],
                'tms_maintenance_bills_vehicle_bill_no_unique'
            );
        });
    }

    private function dropIndexIfExists(string $name): void
    {
        foreach (Schema::getIndexes('tms_maintenance_bills') as $index) {
            if (($index['name'] ?? '') === $name) {
                Schema::table('tms_maintenance_bills', function (Blueprint $table) use ($name) {
                    $table->dropIndex($name);
                });

                return;
            }
        }
    }
};
