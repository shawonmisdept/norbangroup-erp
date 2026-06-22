<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('permissions');
            $table->timestamps();
        });

        $now = now();

        DB::table('roles')->insert([
            [
                'name'        => 'Administrator',
                'permissions' => json_encode([
                    'orders.view', 'orders.update', 'orders.download', 'users.manage', 'roles.manage', 'settings.manage',
                ]),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Manager',
                'permissions' => json_encode([
                    'orders.view', 'orders.update', 'orders.download',
                ]),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Viewer',
                'permissions' => json_encode([
                    'orders.view', 'orders.download',
                ]),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
