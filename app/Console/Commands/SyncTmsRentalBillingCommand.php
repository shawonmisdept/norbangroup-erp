<?php

namespace App\Console\Commands;

use App\Services\Tms\DailyRentalBillingService;
use Illuminate\Console\Command;

class SyncTmsRentalBillingCommand extends Command
{
    protected $signature = 'tms:sync-rental-billing
                            {--from= : Start date Y-m-d (default 7 days ago)}
                            {--to= : End date Y-m-d (default today)}';

    protected $description = 'Create missing rental vehicle daily KM charges from odometer logs';

    public function handle(DailyRentalBillingService $billing): int
    {
        $result = $billing->catchUpMissingCharges(
            $this->option('from'),
            $this->option('to'),
        );

        $this->info(sprintf(
            'Processed %d log(s): %d charge(s) created, %d skipped.',
            $result['processed'],
            $result['created'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
