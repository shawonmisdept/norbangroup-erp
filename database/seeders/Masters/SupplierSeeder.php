<?php

namespace Database\Seeders\Masters;

use App\Models\Supplier;
use App\Models\SupplierType;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['name' => 'Square Textiles Ltd', 'type' => 'Fabric Supplier', 'company' => 'Square Textiles Ltd', 'email' => 'sales@squaretextiles.com', 'phone' => '01722222201', 'country' => 'Bangladesh'],
            ['name' => 'Pacific Yarn Co.', 'type' => 'Yarn Supplier', 'company' => 'Pacific Yarn Co.', 'email' => 'info@pacificyarn.com', 'phone' => '01722222202', 'country' => 'Bangladesh'],
            ['name' => 'Global Trims BD', 'type' => 'Trims Supplier', 'company' => 'Global Trims BD', 'email' => 'order@globaltrims.bd', 'phone' => '01722222203', 'country' => 'Bangladesh'],
            ['name' => 'Metro Accessories', 'type' => 'Accessories Supplier', 'company' => 'Metro Accessories', 'email' => 'sales@metroacc.com', 'phone' => '01722222204', 'country' => 'Bangladesh'],
            ['name' => 'Print Pro Ltd', 'type' => 'Printing Supplier', 'company' => 'Print Pro Ltd', 'email' => 'hello@printpro.bd', 'phone' => '01722222205', 'country' => 'Bangladesh'],
        ];

        foreach ($records as $record) {
            Supplier::updateOrCreate(
                ['name' => $record['name']],
                [
                    'supplier_type_id' => SupplierType::where('name', $record['type'])->value('id'),
                    'company'          => $record['company'],
                    'email'            => $record['email'],
                    'phone'            => $record['phone'],
                    'country'          => $record['country'],
                    'is_active'        => true,
                ]
            );
        }
    }
}
