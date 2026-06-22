<?php

namespace Database\Seeders\Masters;

use App\Models\PaymentStatus;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class PaymentStatusSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return PaymentStatus::class; }

    protected function records(): array
    {
        return [
            ['name' => 'Unpaid'],
            ['name' => 'Partially Paid'],
            ['name' => 'Paid'],
            ['name' => 'Overdue'],
            ['name' => 'On Hold'],
        ];
    }
}
