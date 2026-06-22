<?php

namespace Database\Seeders\Masters;

use App\Models\SupplierType;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class SupplierTypeSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return SupplierType::class; }

    protected function records(): array
    {
        return [
            ['name' => 'Fabric Supplier'],
            ['name' => 'Yarn Supplier'],
            ['name' => 'Trims Supplier'],
            ['name' => 'Accessories Supplier'],
            ['name' => 'Printing Supplier'],
        ];
    }
}
