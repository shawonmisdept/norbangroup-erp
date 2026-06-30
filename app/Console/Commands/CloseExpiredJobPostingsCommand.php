<?php

namespace App\Console\Commands;

use App\Services\Hrm\JobPostingService;
use Illuminate\Console\Command;

class CloseExpiredJobPostingsCommand extends Command
{
    protected $signature = 'hrm:close-expired-job-postings';

    protected $description = 'Close job postings whose application deadline has passed';

    public function handle(JobPostingService $service): int
    {
        $count = $service->closeExpiredPostings();

        $this->info("Closed {$count} expired job posting(s).");

        return self::SUCCESS;
    }
}
