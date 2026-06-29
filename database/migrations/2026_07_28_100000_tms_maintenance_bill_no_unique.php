<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tms_maintenance_bills')) {
            return;
        }

        foreach (DB::table('tms_maintenance_bills')->select(['id', 'bill_no'])->get() as $row) {
            $trimmed = trim((string) $row->bill_no);
            if ($trimmed !== $row->bill_no) {
                DB::table('tms_maintenance_bills')->where('id', $row->id)->update(['bill_no' => $trimmed]);
            }
        }

        try {
            Schema::table('tms_maintenance_bills', function (Blueprint $table) {
                $table->unique('bill_no', 'tms_maintenance_bills_bill_no_unique');
            });
        } catch (\Throwable) {
            // Index may already exist, or duplicate bill numbers must be resolved first.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tms_maintenance_bills')) {
            return;
        }

        try {
            Schema::table('tms_maintenance_bills', function (Blueprint $table) {
                $table->dropUnique('tms_maintenance_bills_bill_no_unique');
            });
        } catch (\Throwable) {
            //
        }
    }
};
