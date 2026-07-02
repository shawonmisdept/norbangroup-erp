<?php

namespace App\Services\Commercial;

class QuoteBreakdownService
{
    /** @return array<string, string> */
    public function garmentTypes(): array
    {
        return config('commercial_quote.garment_types', []);
    }

    /** @return array<string, string> */
    public function quoteBases(): array
    {
        return config('commercial_quote.quote_bases', []);
    }

    /** @return array<string, string> */
    public function currencies(): array
    {
        return config('commercial_quote.currencies', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function template(string $garmentType, string $quoteBasis, int $quantity, ?array $saved = null): array
    {
        $sectionsConfig = config("commercial_quote.templates.{$garmentType}", []);

        if ($sectionsConfig === []) {
            $garmentType = 'woven';
            $sectionsConfig = config('commercial_quote.templates.woven', []);
        }

        $savedLines = $this->indexSavedLines($saved);
        $sections = [];

        foreach ($sectionsConfig as $sectionConfig) {
            if (! $this->sectionVisible($sectionConfig, $quoteBasis)) {
                continue;
            }

            $lines = [];

            foreach ($sectionConfig['lines'] as $lineConfig) {
                $key = $sectionConfig['code'] . '.' . $lineConfig['code'];
                $savedLine = $savedLines[$key] ?? [];

                $lines[] = $this->buildLineFromConfig($lineConfig, $sectionConfig['code'], $savedLine);
            }

            foreach ($this->customLinesForSection($saved, $sectionConfig['code']) as $customLine) {
                $lines[] = $this->buildLineFromSaved($customLine, true);
            }

            $sections[] = [
                'code'         => $sectionConfig['code'],
                'label'        => $sectionConfig['label'],
                'allow_custom' => (bool) ($sectionConfig['allow_custom'] ?? false),
                'lines'        => $lines,
                'subtotal_pc'  => 0.0,
            ];
        }

        $breakdown = [
            'garment_type' => $garmentType,
            'quote_basis'  => $quoteBasis,
            'currency'     => $saved['currency'] ?? 'BDT',
            'quantity'     => max(1, $quantity),
            'sections'     => $sections,
            'summary'      => [
                'price_per_pc' => null,
                'order_total'  => null,
            ],
        ];

        return $this->calculate($breakdown);
    }

    /**
     * @param  array<string, mixed>  $breakdown
     * @return array<string, mixed>
     */
    public function calculate(array $breakdown): array
    {
        $quantity = max(1, (int) ($breakdown['quantity'] ?? 1));

        foreach ($breakdown['sections'] as &$section) {
            foreach ($section['lines'] as &$line) {
                if (! ($line['enabled'] ?? true) || ($line['calc'] ?? '') === 'percent') {
                    if (! ($line['enabled'] ?? true)) {
                        $line['computed_pc'] = 0.0;
                    }
                    continue;
                }

                $line['computed_pc'] = round($this->computeLineAmount($line, $quantity, []), 4);
            }
            unset($line);

            $section['subtotal_pc'] = round($this->sectionSubtotal($section), 4);
        }
        unset($section);

        $bases = $this->buildBases($breakdown);

        foreach ($breakdown['sections'] as &$section) {
            foreach ($section['lines'] as &$line) {
                if (! ($line['enabled'] ?? true) || ($line['calc'] ?? '') !== 'percent') {
                    continue;
                }

                $line['computed_pc'] = round($this->computeLineAmount($line, $quantity, $bases), 4);

                if (($line['percent_base'] ?? '') === 'trims_subtotal') {
                    $bases['trims_subtotal'] += $line['computed_pc'];
                }
            }
            unset($line);

            $section['subtotal_pc'] = round($this->sectionSubtotal($section), 4);

            if ($section['code'] === 'overhead') {
                $bases['before_profit'] = $bases['before_overhead'] + $section['subtotal_pc'];
            }
        }
        unset($section);

        $pricePerPc = round(collect($breakdown['sections'])->sum('subtotal_pc'), 2);

        $breakdown['summary'] = [
            'price_per_pc' => $pricePerPc,
            'order_total'  => round($pricePerPc * $quantity, 2),
        ];

        return $breakdown;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeFromRequest(array $payload, int $quantity): array
    {
        $garmentType = $payload['garment_type'] ?? 'woven';
        $quoteBasis = $payload['quote_basis'] ?? 'fob';

        if (! array_key_exists($garmentType, $this->garmentTypes())) {
            $garmentType = 'woven';
        }

        if (! array_key_exists($quoteBasis, $this->quoteBases())) {
            $quoteBasis = 'fob';
        }

        $saved = [
            'currency' => $payload['currency'] ?? 'BDT',
            'sections' => is_array($payload['sections'] ?? null) ? $payload['sections'] : [],
        ];

        return $this->template($garmentType, $quoteBasis, $quantity, $saved);
    }

    /** @param  array<string, mixed>|null  $saved */
    private function indexSavedLines(?array $saved): array
    {
        if ($saved === null) {
            return [];
        }

        $indexed = [];

        foreach ($saved['sections'] ?? [] as $section) {
            foreach ($section['lines'] ?? [] as $line) {
                $indexed[($section['code'] ?? '') . '.' . ($line['code'] ?? '')] = $line;
            }
        }

        return $indexed;
    }

    /** @param  array<string, mixed>  $lineConfig */
    private function buildLineFromConfig(array $lineConfig, string $sectionCode, array $savedLine): array
    {
        return [
            'code'         => $lineConfig['code'],
            'label'        => $lineConfig['label'],
            'calc'         => $lineConfig['calc'],
            'unit'         => $lineConfig['unit'] ?? null,
            'optional'     => (bool) ($lineConfig['optional'] ?? false),
            'custom'       => false,
            'enabled'      => array_key_exists('enabled', $savedLine)
                ? (bool) $savedLine['enabled']
                : ! ($lineConfig['optional'] ?? false),
            'consumption'  => (float) ($savedLine['consumption'] ?? 0),
            'rate'         => (float) ($savedLine['rate'] ?? 0),
            'wastage_pct'  => (float) ($savedLine['wastage_pct'] ?? ($lineConfig['default_wastage_pct'] ?? 0)),
            'amount_pc'    => (float) ($savedLine['amount_pc'] ?? 0),
            'lump_total'   => (float) ($savedLine['lump_total'] ?? 0),
            'percent'      => (float) ($savedLine['percent'] ?? ($lineConfig['default_percent'] ?? 0)),
            'percent_base' => $lineConfig['percent_base'] ?? null,
            'computed_pc'  => 0.0,
        ];
    }

    /** @param  array<string, mixed>  $line */
    private function buildLineFromSaved(array $line, bool $custom = false): array
    {
        return [
            'code'         => (string) ($line['code'] ?? 'custom_' . uniqid()),
            'label'        => (string) ($line['label'] ?? 'Custom item'),
            'calc'         => (string) ($line['calc'] ?? 'amount'),
            'unit'         => $line['unit'] ?? 'kg',
            'optional'     => true,
            'custom'       => $custom || (bool) ($line['custom'] ?? false),
            'enabled'      => (bool) ($line['enabled'] ?? true),
            'consumption'  => (float) ($line['consumption'] ?? 0),
            'rate'         => (float) ($line['rate'] ?? 0),
            'wastage_pct'  => (float) ($line['wastage_pct'] ?? 0),
            'amount_pc'    => (float) ($line['amount_pc'] ?? 0),
            'lump_total'   => (float) ($line['lump_total'] ?? 0),
            'percent'      => (float) ($line['percent'] ?? 0),
            'percent_base' => $line['percent_base'] ?? null,
            'computed_pc'  => 0.0,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $saved
     * @return list<array<string, mixed>>
     */
    private function customLinesForSection(?array $saved, string $sectionCode): array
    {
        if ($saved === null) {
            return [];
        }

        $garmentType = $saved['garment_type'] ?? 'woven';
        $templateSection = collect(config("commercial_quote.templates.{$garmentType}", []))
            ->firstWhere('code', $sectionCode);
        $knownCodes = collect($templateSection['lines'] ?? [])->pluck('code')->all();
        $custom = [];

        foreach ($saved['sections'] ?? [] as $section) {
            if (($section['code'] ?? '') !== $sectionCode) {
                continue;
            }

            foreach ($section['lines'] ?? [] as $line) {
                if (($line['custom'] ?? false) || ! in_array($line['code'] ?? '', $knownCodes, true)) {
                    $custom[] = $line;
                }
            }
        }

        return $custom;
    }

    /** @param  array<string, mixed>  $sectionConfig */
    private function sectionVisible(array $sectionConfig, string $quoteBasis): bool
    {
        $visibleFor = $sectionConfig['visible_for'] ?? null;

        if ($visibleFor === null) {
            return true;
        }

        return in_array($quoteBasis, $visibleFor, true);
    }

    /** @param  array<string, mixed>  $section */
    private function sectionSubtotal(array $section): float
    {
        return (float) collect($section['lines'] ?? [])
            ->sum(fn (array $line) => (float) ($line['computed_pc'] ?? 0));
    }

    /**
     * @param  array<string, mixed>  $breakdown
     * @return array{materials: float, trims_subtotal: float, processing: float, before_overhead: float, before_profit: float}
     */
    private function buildBases(array $breakdown): array
    {
        $materialCodes = ['fabric', 'yarn_fabric'];
        $processingCodes = ['processing', 'value_addition'];
        $excludeFromBeforeOverhead = ['overhead', 'profit', 'development'];

        $materials = 0.0;
        $trimsSubtotal = 0.0;
        $processing = 0.0;
        $beforeOverhead = 0.0;

        foreach ($breakdown['sections'] as $section) {
            $code = $section['code'] ?? '';
            $subtotal = (float) ($section['subtotal_pc'] ?? 0);

            if (in_array($code, $materialCodes, true)) {
                $materials += $subtotal;
            }

            if ($code === 'trims') {
                $trimsSubtotal = (float) collect($section['lines'] ?? [])
                    ->reject(fn (array $line) => ($line['code'] ?? '') === 'trims_wastage')
                    ->sum(fn (array $line) => (float) ($line['computed_pc'] ?? 0));
            }

            if (in_array($code, $processingCodes, true)) {
                $processing += $subtotal;
            }

            if (! in_array($code, $excludeFromBeforeOverhead, true)) {
                $beforeOverhead += $subtotal;
            }
        }

        return [
            'materials'        => $materials,
            'trims_subtotal'   => $trimsSubtotal,
            'processing'       => $processing,
            'before_overhead'  => $beforeOverhead,
            'before_profit'    => $beforeOverhead,
        ];
    }

    /**
     * @param  array<string, mixed>  $line
     * @param  array<string, float>  $bases
     */
    private function computeLineAmount(array $line, int $quantity, array $bases): float
    {
        return match ($line['calc'] ?? 'amount') {
            'consumption' => $this->consumptionAmount($line),
            'lump'        => $quantity > 0 ? ((float) ($line['lump_total'] ?? 0)) / $quantity : 0.0,
            'percent'     => $this->percentAmount($line, $bases),
            default       => (float) ($line['amount_pc'] ?? 0),
        };
    }

    /** @param  array<string, mixed>  $line */
    private function consumptionAmount(array $line): float
    {
        $consumption = (float) ($line['consumption'] ?? 0);
        $rate = (float) ($line['rate'] ?? 0);
        $wastage = (float) ($line['wastage_pct'] ?? 0);

        if ($consumption <= 0 || $rate <= 0) {
            return 0.0;
        }

        return $consumption * $rate * (1 + ($wastage / 100));
    }

    /**
     * @param  array<string, mixed>  $line
     * @param  array<string, float>  $bases
     */
    private function percentAmount(array $line, array $bases): float
    {
        $percent = (float) ($line['percent'] ?? 0);

        if ($percent <= 0) {
            return 0.0;
        }

        $base = match ($line['percent_base'] ?? '') {
            'materials'       => $bases['materials'],
            'trims_subtotal'  => $bases['trims_subtotal'],
            'processing'      => $bases['processing'],
            'before_overhead' => $bases['before_overhead'],
            'before_profit'   => $bases['before_profit'],
            default           => 0.0,
        };

        return $base * ($percent / 100);
    }
}
