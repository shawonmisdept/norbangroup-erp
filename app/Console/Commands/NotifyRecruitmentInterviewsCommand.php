<?php

namespace App\Console\Commands;

use App\Models\Hrm\RecruitmentInterview;
use App\Services\Hrm\RecruitmentMessagingService;
use Illuminate\Console\Command;

class NotifyRecruitmentInterviewsCommand extends Command
{
    protected $signature = 'hrm:notify-recruitment-interviews';

    protected $description = 'Send interview reminders to candidates (24 hours before)';

    public function handle(RecruitmentMessagingService $messaging): int
    {
        $windowStart = now();
        $windowEnd = now()->addDay();

        $interviews = RecruitmentInterview::query()
            ->where('result', 'pending')
            ->whereNull('reminder_sent_at')
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->with(['application.jobPosting', 'application.factory'])
            ->get();

        $count = 0;

        foreach ($interviews as $interview) {
            $messaging->interviewReminder($interview);
            $interview->update(['reminder_sent_at' => now()]);
            $count++;
        }

        $this->info("Sent {$count} interview reminder(s).");

        return self::SUCCESS;
    }
}
