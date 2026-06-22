<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')->constrained()->nullOnDelete();
        });

        $roleMap = DB::table('roles')->pluck('id', 'name');

        $legacyMap = [
            'admin'   => 'Administrator',
            'manager' => 'Manager',
            'viewer'  => 'Viewer',
        ];

        if (Schema::hasColumn('users', 'role')) {
            foreach (DB::table('users')->get() as $user) {
                $roleName = $legacyMap[$user->role] ?? 'Viewer';
                $roleId = $roleMap[$roleName] ?? $roleMap->first();

                DB::table('users')->where('id', $user->id)->update(['role_id' => $roleId]);
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

        DB::table('users')->whereNull('role_id')->update([
            'role_id' => DB::table('roles')->where('name', 'Viewer')->value('id'),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('viewer')->after('email');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
