<?php

namespace Database\Seeders\Concerns;

trait SeedsMasterRecords
{
    abstract protected function modelClass(): string;

    abstract protected function records(): array;

    public function run(): void
    {
        $model = $this->modelClass();

        foreach ($this->records() as $record) {
            $attributes = array_merge(['is_active' => true], $record);

            $model::updateOrCreate(
                ['name' => $attributes['name']],
                $attributes
            );
        }
    }

    protected function recordsFromDataFile(string $filename): array
    {
        return require database_path('seeders/data/' . $filename);
    }
}
