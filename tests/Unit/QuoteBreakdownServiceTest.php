<?php

namespace Tests\Unit;

use App\Services\Commercial\QuoteBreakdownService;
use Tests\TestCase;

class QuoteBreakdownServiceTest extends TestCase
{
    private QuoteBreakdownService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(QuoteBreakdownService::class);
    }

    public function test_fob_woven_includes_logistics_section(): void
    {
        $breakdown = $this->service->template('woven', 'fob', 500);

        $codes = collect($breakdown['sections'])->pluck('code')->all();

        $this->assertContains('fabric', $codes);
        $this->assertContains('logistics', $codes);
    }

    public function test_cm_woven_excludes_logistics_section(): void
    {
        $breakdown = $this->service->template('woven', 'cm', 500);

        $codes = collect($breakdown['sections'])->pluck('code')->all();

        $this->assertContains('fabric', $codes);
        $this->assertNotContains('logistics', $codes);
    }

    public function test_knit_template_uses_yarn_section_not_woven_fabric(): void
    {
        $breakdown = $this->service->template('knit', 'fob', 1000);

        $codes = collect($breakdown['sections'])->pluck('code')->all();

        $this->assertContains('yarn_fabric', $codes);
        $this->assertNotContains('fabric', $codes);
    }

    public function test_calculates_consumption_and_order_total(): void
    {
        $breakdown = $this->service->template('woven', 'cm', 500);

        $fabricSection = collect($breakdown['sections'])->firstWhere('code', 'fabric');
        $mainFabric = collect($fabricSection['lines'])->firstWhere('code', 'main_fabric');
        $mainFabric['consumption'] = 0.2;
        $mainFabric['rate'] = 400;
        $mainFabric['wastage_pct'] = 5;
        $mainFabric['enabled'] = true;

        $processingSection = collect($breakdown['sections'])->firstWhere('code', 'processing');
        $cmLine = collect($processingSection['lines'])->firstWhere('code', 'cutting_making');
        $cmLine['amount_pc'] = 50;
        $cmLine['enabled'] = true;

        $payload = [
            'garment_type' => 'woven',
            'quote_basis'  => 'cm',
            'currency'     => 'BDT',
            'sections'     => collect($breakdown['sections'])->map(function ($section) use ($fabricSection, $processingSection, $mainFabric, $cmLine) {
                if ($section['code'] === 'fabric') {
                    $section['lines'] = collect($section['lines'])->map(
                        fn ($line) => $line['code'] === 'main_fabric' ? $mainFabric : $line
                    )->values()->all();
                }

                if ($section['code'] === 'processing') {
                    $section['lines'] = collect($section['lines'])->map(
                        fn ($line) => $line['code'] === 'cutting_making' ? $cmLine : $line
                    )->values()->all();
                }

                return $section;
            })->all(),
        ];

        $calculated = $this->service->normalizeFromRequest($payload, 500);

        // 0.2 × 400 × 1.05 = 84.00 / pc fabric + 50 CM + overhead/profit defaults
        $this->assertGreaterThan(134, (float) $calculated['summary']['price_per_pc']);
        $this->assertSame(
            round((float) $calculated['summary']['price_per_pc'] * 500, 2),
            (float) $calculated['summary']['order_total']
        );
    }
}
