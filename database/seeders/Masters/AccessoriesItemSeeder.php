<?php

namespace Database\Seeders\Masters;

use App\Models\AccessoriesItem;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class AccessoriesItemSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return AccessoriesItem::class;
    }

    protected function records(): array
    {
        return array_map(function (array $record) {
            return [
                'name'        => $record['name'],
                'description' => $record['description'] ?? null,
                'is_active'   => $record['is_active'] ?? true,
            ];
        }, $this->recordsFromDataFile('accessories_items.php'));
    }
}
