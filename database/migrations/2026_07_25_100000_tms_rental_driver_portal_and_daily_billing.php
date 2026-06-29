<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tms_rental_driver_portal_users')) {
            Schema::create('tms_rental_driver_portal_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rental_driver_id')->unique()->constrained('tms_rental_drivers')->cascadeOnDelete();
                $table->string('password');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_login_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('tms_daily_odometer_logs', 'morning_entered_by_rental_driver')) {
            Schema::table('tms_daily_odometer_logs', function (Blueprint $table) {
                $table->foreignId('morning_entered_by_rental_driver')->nullable()->after('evening_entered_by_employee')
                    ->constrained('tms_rental_drivers')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('tms_daily_odometer_logs', 'evening_entered_by_rental_driver')) {
            Schema::table('tms_daily_odometer_logs', function (Blueprint $table) {
                $table->foreignId('evening_entered_by_rental_driver')->nullable()->after('morning_entered_by_rental_driver')
                    ->constrained('tms_rental_drivers')->nullOnDelete();
            });
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::rename('tms_rental_vehicle_charges', 'tms_rental_vehicle_charges_legacy');

            Schema::create('tms_rental_vehicle_charges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trip_log_id')->nullable()->constrained('tms_trip_logs')->nullOnDelete();
                $table->foreignId('odometer_log_id')->nullable()->constrained('tms_daily_odometer_logs')->nullOnDelete();
                $table->date('log_date')->nullable();
                $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
                $table->foreignId('vehicle_id')->constrained('tms_vehicles')->cascadeOnDelete();
                $table->foreignId('rental_vendor_id')->nullable()->constrained('tms_rental_vendors')->nullOnDelete();
                $table->decimal('total_km', 10, 2);
                $table->decimal('km_rate', 10, 2);
                $table->decimal('amount', 12, 2);
                $table->string('payment_status', 16)->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique('odometer_log_id');
            });

            DB::statement(
                'INSERT INTO tms_rental_vehicle_charges (id, trip_log_id, factory_id, vehicle_id, rental_vendor_id, total_km, km_rate, amount, payment_status, paid_at, paid_by, created_at, updated_at)
                 SELECT id, trip_log_id, factory_id, vehicle_id, rental_vendor_id, total_km, km_rate, amount, payment_status, paid_at, paid_by, created_at, updated_at
                 FROM tms_rental_vehicle_charges_legacy'
            );

            Schema::drop('tms_rental_vehicle_charges_legacy');

            return;
        }

        if (! Schema::hasColumn('tms_rental_vehicle_charges', 'odometer_log_id')) {
            Schema::table('tms_rental_vehicle_charges', function (Blueprint $table) {
                $table->foreignId('odometer_log_id')->nullable()->after('trip_log_id')
                    ->constrained('tms_daily_odometer_logs')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('tms_rental_vehicle_charges', 'log_date')) {
            Schema::table('tms_rental_vehicle_charges', function (Blueprint $table) {
                $table->date('log_date')->nullable()->after('odometer_log_id');
            });
        }

        if (! $this->mysqlIndexExists('tms_rental_vehicle_charges', 'tms_rental_vehicle_charges_odometer_log_id_unique')) {
            Schema::table('tms_rental_vehicle_charges', function (Blueprint $table) {
                $table->unique('odometer_log_id');
            });
        }

        $this->relaxMysqlTripLogIdColumn('tms_rental_vehicle_charges');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            $this->safeMysqlDropForeignKeys('tms_rental_vehicle_charges', 'trip_log_id');
            DB::statement('ALTER TABLE tms_rental_vehicle_charges MODIFY trip_log_id BIGINT UNSIGNED NOT NULL');

            if (! $this->mysqlIndexExists('tms_rental_vehicle_charges', 'tms_rental_vehicle_charges_trip_log_id_unique')) {
                Schema::table('tms_rental_vehicle_charges', function (Blueprint $table) {
                    $table->unique('trip_log_id');
                });
            }

            if (! $this->mysqlForeignKeyExists('tms_rental_vehicle_charges', 'trip_log_id')) {
                Schema::table('tms_rental_vehicle_charges', function (Blueprint $table) {
                    $table->foreign('trip_log_id')->references('id')->on('tms_trip_logs')->cascadeOnDelete();
                });
            }
        }

        Schema::table('tms_rental_vehicle_charges', function (Blueprint $table) {
            if (Schema::hasColumn('tms_rental_vehicle_charges', 'odometer_log_id')) {
                $table->dropConstrainedForeignId('odometer_log_id');
            }
            if (Schema::hasColumn('tms_rental_vehicle_charges', 'log_date')) {
                $table->dropColumn('log_date');
            }
        });

        Schema::table('tms_daily_odometer_logs', function (Blueprint $table) {
            if (Schema::hasColumn('tms_daily_odometer_logs', 'evening_entered_by_rental_driver')) {
                $table->dropConstrainedForeignId('evening_entered_by_rental_driver');
            }
            if (Schema::hasColumn('tms_daily_odometer_logs', 'morning_entered_by_rental_driver')) {
                $table->dropConstrainedForeignId('morning_entered_by_rental_driver');
            }
        });

        Schema::dropIfExists('tms_rental_driver_portal_users');
    }

    private function relaxMysqlTripLogIdColumn(string $table): void
    {
        if ($this->mysqlColumnIsNullable($table, 'trip_log_id')) {
            return;
        }

        $this->safeMysqlDropForeignKeys($table, 'trip_log_id');
        $this->safeMysqlDropUniqueIndexesOnColumn($table, 'trip_log_id');

        DB::statement("ALTER TABLE `{$table}` MODIFY `trip_log_id` BIGINT UNSIGNED NULL");

        if (! $this->mysqlForeignKeyExists($table, 'trip_log_id')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreign('trip_log_id')->references('id')->on('tms_trip_logs')->nullOnDelete();
            });
        }
    }

    private function safeMysqlDropForeignKeys(string $table, string $column): void
    {
        foreach ($this->mysqlForeignKeyNames($table, $column) as $constraint) {
            try {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
            } catch (\Throwable) {
                // Production DBs may report stale/missing FK names — continue.
            }
        }

        // Also try Laravel's default name in case information_schema missed it.
        try {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$table}_{$column}_foreign`");
        } catch (\Throwable) {
            // Ignore when the constraint is absent.
        }
    }

    private function safeMysqlDropUniqueIndexesOnColumn(string $table, string $column): void
    {
        foreach ($this->mysqlUniqueIndexNamesOnColumn($table, $column) as $indexName) {
            try {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
            } catch (\Throwable) {
                // Ignore missing or FK-bound indexes.
            }
        }
    }

    private function mysqlColumnIsNullable(string $table, string $column): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        $rows = DB::select(
            'SELECT IS_NULLABLE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1',
            [$table, $column]
        );

        return ($rows[0]->IS_NULLABLE ?? 'NO') === 'YES';
    }

    private function mysqlForeignKeyExists(string $table, string $column): bool
    {
        return count($this->mysqlForeignKeyNames($table, $column)) > 0;
    }

    /** @return list<string> */
    private function mysqlForeignKeyNames(string $table, string $column): array
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return [];
        }

        $rows = DB::select(
            'SELECT kcu.CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS tc
             INNER JOIN information_schema.KEY_COLUMN_USAGE kcu
                 ON tc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
                AND tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                AND tc.TABLE_NAME = kcu.TABLE_NAME
             WHERE tc.CONSTRAINT_SCHEMA = DATABASE()
               AND tc.TABLE_NAME = ?
               AND tc.CONSTRAINT_TYPE = "FOREIGN KEY"
               AND kcu.COLUMN_NAME = ?',
            [$table, $column]
        );

        return array_values(array_unique(array_map(static fn ($row) => $row->CONSTRAINT_NAME, $rows)));
    }

    /** @return list<string> */
    private function mysqlUniqueIndexNamesOnColumn(string $table, string $column): array
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return [];
        }

        $rows = DB::select(
            'SELECT DISTINCT INDEX_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND NON_UNIQUE = 0
               AND INDEX_NAME <> "PRIMARY"',
            [$table, $column]
        );

        return array_values(array_map(static fn ($row) => $row->INDEX_NAME, $rows));
    }

    private function mysqlIndexExists(string $table, string $indexName): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        $rows = DB::select(
            'SELECT INDEX_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?
             LIMIT 1',
            [$table, $indexName]
        );

        return count($rows) > 0;
    }
};
