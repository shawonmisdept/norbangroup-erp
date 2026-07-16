<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, string> legacy name => new name */
    private const RENAMES = [
        'Norban Comtex Limited' => 'NCL',
        'Hornbill Apparel Ltd' => 'HAL',
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $legacyName => $newName) {
            $legacy = DB::table('factories')->where('name', $legacyName)->first();

            if (! $legacy) {
                continue;
            }

            $duplicate = DB::table('factories')
                ->where('name', $newName)
                ->where('id', '!=', $legacy->id)
                ->exists();

            if ($duplicate) {
                continue;
            }

            DB::table('factories')->where('id', $legacy->id)->update(['name' => $newName]);
        }
    }

    public function down(): void
    {
        foreach (self::RENAMES as $legacyName => $newName) {
            DB::table('factories')->where('name', $newName)->update(['name' => $legacyName]);
        }
    }
};
