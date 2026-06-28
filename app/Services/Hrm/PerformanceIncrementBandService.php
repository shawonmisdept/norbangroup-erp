<?php

namespace App\Services\Hrm;

use App\Models\Hrm\PerformanceIncrementBand;
use Illuminate\Support\Collection;

class PerformanceIncrementBandService
{
    /** @return Collection<int, PerformanceIncrementBand> */
    public function bandsForFactory(?int $factoryId): Collection
    {
        if ($factoryId) {
            $factoryBands = PerformanceIncrementBand::query()
                ->where('factory_id', $factoryId)
                ->where('is_active', true)
                ->orderByDesc('min_score')
                ->get();

            if ($factoryBands->isNotEmpty()) {
                return $factoryBands;
            }
        }

        $global = PerformanceIncrementBand::query()
            ->whereNull('factory_id')
            ->where('is_active', true)
            ->orderByDesc('min_score')
            ->get();

        if ($global->isNotEmpty()) {
            return $global;
        }

        return $this->seedDefaultBands($factoryId);
    }

    public function matchBand(float $score, ?int $factoryId): ?PerformanceIncrementBand
    {
        return $this->bandsForFactory($factoryId)
            ->first(fn (PerformanceIncrementBand $band) => $band->matchesScore($score));
    }

    /** @return Collection<int, PerformanceIncrementBand> */
    public function seedDefaultBands(?int $factoryId = null): Collection
    {
        $defaults = config('hrm.performance.default_increment_bands', []);
        $created = collect();

        foreach ($defaults as $index => $row) {
            $created->push(PerformanceIncrementBand::create([
                'factory_id'        => $factoryId,
                'name'              => $row['name'],
                'min_score'         => $row['min_score'],
                'max_score'         => $row['max_score'],
                'increment_percent' => $row['increment_percent'],
                'sort_order'        => $row['sort_order'] ?? $index,
                'is_active'         => true,
            ]));
        }

        return $created->sortByDesc('min_score')->values();
    }

    /** @param array<int, array<string, mixed>> $bands */
    public function syncFactoryBands(int $factoryId, array $bands): void
    {
        PerformanceIncrementBand::query()->where('factory_id', $factoryId)->delete();

        foreach ($bands as $index => $row) {
            PerformanceIncrementBand::create([
                'factory_id'        => $factoryId,
                'name'              => $row['name'],
                'min_score'         => $row['min_score'],
                'max_score'         => $row['max_score'],
                'increment_percent' => $row['increment_percent'],
                'sort_order'        => $row['sort_order'] ?? $index,
                'is_active'         => (bool) ($row['is_active'] ?? true),
            ]);
        }
    }
}
