<?php

namespace App\Services\Hrm;

use App\Models\Hrm\PerformanceBonusBand;
use Illuminate\Support\Collection;

class PerformanceBonusBandService
{
    /** @return Collection<int, PerformanceBonusBand> */
    public function bandsForFactory(?int $factoryId): Collection
    {
        if ($factoryId) {
            $factoryBands = PerformanceBonusBand::query()
                ->where('factory_id', $factoryId)
                ->where('is_active', true)
                ->orderByDesc('min_score')
                ->get();

            if ($factoryBands->isNotEmpty()) {
                return $factoryBands;
            }
        }

        $global = PerformanceBonusBand::query()
            ->whereNull('factory_id')
            ->where('is_active', true)
            ->orderByDesc('min_score')
            ->get();

        if ($global->isNotEmpty()) {
            return $global;
        }

        return $this->seedDefaultBands($factoryId);
    }

    public function matchBand(float $score, ?int $factoryId): ?PerformanceBonusBand
    {
        return $this->bandsForFactory($factoryId)
            ->first(fn (PerformanceBonusBand $band) => $band->matchesScore($score));
    }

    /** @return Collection<int, PerformanceBonusBand> */
    public function seedDefaultBands(?int $factoryId = null): Collection
    {
        $defaults = config('hrm.performance.default_bonus_bands', []);
        $created = collect();

        foreach ($defaults as $index => $row) {
            $created->push(PerformanceBonusBand::create([
                'factory_id'    => $factoryId,
                'name'          => $row['name'],
                'min_score'     => $row['min_score'],
                'max_score'     => $row['max_score'],
                'bonus_percent' => $row['bonus_percent'],
                'sort_order'    => $row['sort_order'] ?? $index,
                'is_active'     => true,
            ]));
        }

        return $created->sortByDesc('min_score')->values();
    }

    /** @param array<int, array<string, mixed>> $bands */
    public function syncFactoryBands(int $factoryId, array $bands): void
    {
        PerformanceBonusBand::query()->where('factory_id', $factoryId)->delete();

        foreach ($bands as $index => $row) {
            PerformanceBonusBand::create([
                'factory_id'    => $factoryId,
                'name'          => $row['name'],
                'min_score'     => $row['min_score'],
                'max_score'     => $row['max_score'],
                'bonus_percent' => $row['bonus_percent'],
                'sort_order'    => $row['sort_order'] ?? $index,
                'is_active'     => (bool) ($row['is_active'] ?? true),
            ]);
        }
    }
}
