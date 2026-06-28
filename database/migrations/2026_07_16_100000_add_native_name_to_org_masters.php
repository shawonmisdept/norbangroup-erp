<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'factories',
        'departments',
        'designations',
        'hrm_buildings',
        'hrm_floors',
        'hrm_lines',
        'hrm_worker_categories',
        'hrm_leave_types',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'native_name')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->string('native_name')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'native_name')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('native_name');
            });
        }
    }
};
