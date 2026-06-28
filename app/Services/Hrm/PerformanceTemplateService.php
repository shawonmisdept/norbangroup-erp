<?php

namespace App\Services\Hrm;

use App\Models\Factory;
use App\Models\Hrm\PerformanceTemplate;
use App\Models\Hrm\PerformanceTemplateCriterion;
use App\Models\User;

class PerformanceTemplateService
{
    public function resolveForFactory(?int $factoryId, string $cycleType): PerformanceTemplate
    {
        if ($factoryId) {
            $factoryTemplate = PerformanceTemplate::query()
                ->where('factory_id', $factoryId)
                ->where('is_active', true)
                ->where(function ($q) use ($cycleType) {
                    $q->whereNull('cycle_types')
                        ->orWhereJsonContains('cycle_types', $cycleType);
                })
                ->orderByDesc('is_default')
                ->first();

            if ($factoryTemplate) {
                return $factoryTemplate->load('criteria');
            }
        }

        $global = PerformanceTemplate::query()
            ->whereNull('factory_id')
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if ($global) {
            return $global->load('criteria');
        }

        return $this->ensureDefaultTemplate();
    }

    public function ensureDefaultTemplate(): PerformanceTemplate
    {
        $existing = PerformanceTemplate::query()
            ->whereNull('factory_id')
            ->where('is_default', true)
            ->first();

        if ($existing) {
            if ($existing->criteria()->count() === 0) {
                $this->seedCriteria($existing);
            }

            return $existing->load('criteria');
        }

        $template = PerformanceTemplate::create([
            'factory_id'  => null,
            'name'        => 'Standard Hybrid Template',
            'cycle_types' => null,
            'is_default'  => true,
            'is_active'   => true,
        ]);

        $this->seedCriteria($template);

        return $template->load('criteria');
    }

    public function create(array $data, User $user): PerformanceTemplate
    {
        $template = PerformanceTemplate::create([
            'factory_id'  => $data['factory_id'] ?? null,
            'name'        => $data['name'],
            'cycle_types' => $data['cycle_types'] ?? null,
            'is_default'  => (bool) ($data['is_default'] ?? false),
            'is_active'   => (bool) ($data['is_active'] ?? true),
            'created_by'  => $user->id,
        ]);

        $this->syncCriteria($template, $data['criteria'] ?? config('hrm.performance.default_criteria', []));

        return $template->load('criteria');
    }

    public function update(PerformanceTemplate $template, array $data): PerformanceTemplate
    {
        $template->update([
            'factory_id'  => $data['factory_id'] ?? null,
            'name'        => $data['name'],
            'cycle_types' => $data['cycle_types'] ?? null,
            'is_default'  => (bool) ($data['is_default'] ?? false),
            'is_active'   => (bool) ($data['is_active'] ?? true),
        ]);

        if (isset($data['criteria'])) {
            $this->syncCriteria($template, $data['criteria']);
        }

        return $template->fresh('criteria');
    }

    /** @param array<int, array<string, mixed>> $criteria */
    private function syncCriteria(PerformanceTemplate $template, array $criteria): void
    {
        $template->criteria()->delete();

        foreach ($criteria as $index => $row) {
            PerformanceTemplateCriterion::create([
                'template_id'     => $template->id,
                'code'            => $row['code'],
                'label'           => $row['label'],
                'criterion_type'  => $row['criterion_type'],
                'weight'          => $row['weight'],
                'sort_order'      => $row['sort_order'] ?? $index,
                'config'          => $row['config'] ?? null,
            ]);
        }
    }

    private function seedCriteria(PerformanceTemplate $template): void
    {
        $this->syncCriteria($template, config('hrm.performance.default_criteria', []));
    }

    /** @return array<int, string> */
    public function factoryOptions(): array
    {
        return Factory::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
    }
}
