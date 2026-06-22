<?php

namespace Database\Seeders\Masters;

use App\Models\Material;
use App\Models\MaterialType;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['name' => 'Cotton Yarn 30/1', 'type' => 'Yarn', 'unit' => 'kg'],
            ['name' => 'Polyester Yarn 150D', 'type' => 'Yarn', 'unit' => 'kg'],
            ['name' => 'Single Jersey Cotton', 'type' => 'Fabric', 'unit' => 'kg'],
            ['name' => 'Denim 12oz', 'type' => 'Fabric', 'unit' => 'm'],
            ['name' => 'Poly Twill', 'type' => 'Fabric', 'unit' => 'm'],
            ['name' => 'Main Label', 'type' => 'Trims', 'unit' => 'pcs'],
            ['name' => 'Care Label', 'type' => 'Trims', 'unit' => 'pcs'],
            ['name' => 'Poly Bag', 'type' => 'Packaging', 'unit' => 'pcs'],
        ];

        foreach ($records as $record) {
            Material::updateOrCreate(
                ['name' => $record['name']],
                [
                    'material_type_id' => MaterialType::where('name', $record['type'])->value('id'),
                    'unit'           => $record['unit'],
                    'is_active'      => true,
                ]
            );
        }
    }
}
