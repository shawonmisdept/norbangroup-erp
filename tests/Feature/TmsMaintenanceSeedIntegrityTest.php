<?php

namespace Tests\Feature;

use Tests\TestCase;

class TmsMaintenanceSeedIntegrityTest extends TestCase
{
    public function test_seed_bills_have_matching_item_totals_and_unique_bill_no_per_vehicle(): void
    {
        $data = require database_path('seeders/data/tms_maintenance.php');
        $billCount = 0;

        foreach ($data as $reg => $vehicleData) {
            $seenBillNos = [];

        foreach ($vehicleData['bills'] as $bill) {
            $billCount++;
            $billNo = (string) ($bill['bill_no'] ?? '');
            $billKey = $billNo . '|' . ($bill['bill_date'] ?? '');

            $this->assertNotSame('', $billNo, "Empty bill_no on {$reg}");
            $this->assertArrayNotHasKey(
                $billKey,
                $seenBillNos,
                "Duplicate bill {$billNo} on {$reg} for date {$bill['bill_date']}"
            );
            $seenBillNos[$billKey] = true;

                $itemsSum = round(array_sum(array_map(
                    static fn (array $item) => (float) ($item['amount'] ?? 0),
                    $bill['items'] ?? []
                )), 2);
                $total = round((float) ($bill['total_amount'] ?? 0), 2);

                $this->assertEqualsWithDelta(
                    $total,
                    $itemsSum,
                    0.01,
                    "Amount mismatch on {$reg} bill {$billNo}"
                );
            }
        }

        $this->assertGreaterThan(2400, $billCount);
    }
}
