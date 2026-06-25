<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('hrm_employees')
            ->whereNull('weekend_days')
            ->update(['weekend_days' => json_encode([0])]);
    }

    public function down(): void
    {
        // no-op
    }
};
