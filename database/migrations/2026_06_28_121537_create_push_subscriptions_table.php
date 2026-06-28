<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $connection = config('webpush.database_connection');
        $tableName = config('webpush.table_name');

        Schema::connection($connection)->create($tableName, function (Blueprint $table) use ($connection) {
            $table->bigIncrements('id');
            $table->morphs('subscribable', 'push_subscriptions_subscribable_morph_idx');
            $table->string('endpoint', 500);
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->timestamps();

            if (Schema::connection($connection)->getConnection()->getDriverName() !== 'mysql') {
                $table->unique('endpoint', 'push_subscriptions_endpoint_unique');
            }
        });

        if (Schema::connection($connection)->getConnection()->getDriverName() === 'mysql') {
            DB::connection($connection)->statement(
                "CREATE UNIQUE INDEX push_subscriptions_endpoint_unique ON {$tableName} (endpoint(250))"
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection(config('webpush.database_connection'))->dropIfExists(config('webpush.table_name'));
    }
};
