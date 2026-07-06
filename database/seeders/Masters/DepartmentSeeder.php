<?php

namespace Database\Seeders\Masters;

use App\Models\Factory;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array{factory: string} $headOfficeOrg */
        $headOfficeOrg = require database_path('seeders/data/head_office_org.php');

        $skipFactories = [$headOfficeOrg['factory']];

        $factories = Factory::where('is_active', true)->whereNotIn('name', $skipFactories)->get();

        if ($factories->isEmpty()) {
            $this->command?->info('DepartmentSeeder: no generic unit factories to seed (Head Office uses HeadOfficeOrgSeeder).');

            return;
        }

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

        foreach ($factories as $factory) {
            foreach ($departments as $name) {
                \App\Models\Department::updateOrCreate(
                    ['name' => $name, 'factory_id' => $factory->id],
                    ['is_active' => true]
                );
            }

            \App\Models\Department::where('factory_id', $factory->id)
                ->whereNotIn('name', $departments)
                ->update(['is_active' => false]);
        }
    }
}
