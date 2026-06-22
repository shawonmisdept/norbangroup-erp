<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_code', 20)->nullable()->unique()->after('id');
            $table->string('photo')->nullable()->after('email');
        });

        foreach (DB::table('users')->get() as $user) {
            do {
                $code = 'USR-' . strtoupper(Str::random(6));
            } while (DB::table('users')->where('user_code', $code)->exists());

            DB::table('users')->where('id', $user->id)->update(['user_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_code', 'photo']);
        });
    }
};
