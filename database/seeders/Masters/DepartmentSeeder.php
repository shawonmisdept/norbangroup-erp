<?php

namespace Database\Seeders\Masters;

use App\Models\Department;
use App\Models\Factory;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $unitData = require database_path('seeders/data/unit_departments_designations.php');
        $excelFactories = $unitData['factories'];

        $departments = [
            'Merchandising',
            'Production Planning',
            'Sample Development',
            'Knitting',
            'Dyeing',
            'Fabric Finishing',
            'Cutting',
            'Sewing',
            'Garment Finishing',
            'Washing',
            'Quality Control',
            'Packaging',
            'Warehouse & Store',
            'Industrial Engineering',
            'Maintenance',
        ];

        foreach (Factory::where('is_active', true)->whereNotIn('name', $excelFactories)->get() as $factory) {
            foreach ($departments as $name) {
                Department::updateOrCreate(
                    ['name' => $name, 'factory_id' => $factory->id],
                    ['is_active' => true]
                );
            }

            Department::where('factory_id', $factory->id)
                ->whereNotIn('name', $departments)
                ->update(['is_active' => false]);
        }
    }
}
