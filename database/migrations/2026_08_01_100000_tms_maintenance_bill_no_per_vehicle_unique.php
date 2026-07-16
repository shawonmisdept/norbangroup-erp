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

        $this->dropGlobalBillNoUnique();

        if ($this->hasVehicleBillNoUnique()) {
            return;
        }

        Schema::table('tms_maintenance_bills', function (Blueprint $table) {
            $table->unique(
                ['vehicle_id', 'bill_no'],
                'tms_maintenance_bills_vehicle_bill_no_unique'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tms_maintenance_bills')) {
            return;
        }

        if ($this->hasVehicleBillNoUnique()) {
            Schema::table('tms_maintenance_bills', function (Blueprint $table) {
                $table->dropUnique('tms_maintenance_bills_vehicle_bill_no_unique');
            });
        }

        if (! $this->hasGlobalBillNoUnique()) {
            Schema::table('tms_maintenance_bills', function (Blueprint $table) {
                $table->unique('bill_no', 'tms_maintenance_bills_bill_no_unique');
            });
        }
    }

    private function dropGlobalBillNoUnique(): void
    {
        foreach ($this->billNoOnlyIndexNames() as $name) {
            Schema::table('tms_maintenance_bills', function (Blueprint $table) use ($name) {
                $table->dropIndex($name);
            });
        }
    }

    /** @return list<string> */
    private function billNoOnlyIndexNames(): array
    {
        $names = [];

        foreach (Schema::getIndexes('tms_maintenance_bills') as $index) {
            $columns = array_values($index['columns'] ?? []);
            $name = (string) ($index['name'] ?? '');

            if ($columns === ['bill_no'] && $name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    private function hasVehicleBillNoUnique(): bool
    {
        foreach (Schema::getIndexes('tms_maintenance_bills') as $index) {
            $columns = array_values($index['columns'] ?? []);

            if ($columns === ['vehicle_id', 'bill_no']) {
                return true;
            }
        }

        return false;
    }

    private function hasGlobalBillNoUnique(): bool
    {
        return $this->billNoOnlyIndexNames() !== [];
    }
};
